<?php

declare(strict_types=1);

// Standalone on purpose: never bootstrap the developer's application or load its .env.
$root = dirname(__DIR__);
$temporary = null;
$partial = null;

function directory(string $path, int $mode = 0755): void
{
    if (! is_dir($path) && ! mkdir($path, $mode, true)) {
        throw new RuntimeException("Cannot create directory: {$path}");
    }
}

function removeTree(string $path): void
{
    if (is_link($path) || is_file($path)) {
        if (! unlink($path)) {
            throw new RuntimeException("Cannot remove file: {$path}");
        }
    } elseif (is_dir($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeTree($entry->getPathname());
        }
        if (! rmdir($path)) {
            throw new RuntimeException("Cannot remove directory: {$path}");
        }
    }
}

function run(array $command, string $cwd, array $environment, bool $capture = false): string
{
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => $capture ? ['pipe', 'w'] : STDOUT, 2 => STDERR], $pipes, $cwd, $environment);
    if (! is_resource($process)) {
        throw new RuntimeException('Could not start '.$command[0]);
    }
    fclose($pipes[0]);
    $output = $capture ? stream_get_contents($pipes[1]) : '';
    if ($capture) {
        fclose($pipes[1]);
    }
    if (proc_close($process) !== 0) {
        if ($capture && $output !== '') {
            fwrite(STDERR, $output);
        }
        throw new RuntimeException('Build step failed: '.implode(' ', $command));
    }

    return $output;
}

function copySource(string $root, string $source, string $destination): void
{
    $path = $root.'/'.$source;
    $resolved = realpath($path);
    if (! $resolved || ! is_file($path) || is_link($path) || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
        throw new RuntimeException("Missing or unsafe source file: {$source}");
    }
    directory(dirname($destination));
    if (! copy($path, $destination)) {
        throw new RuntimeException("Could not copy {$source}");
    }
}

try {
    if (PHP_VERSION_ID < 80300 || ! class_exists(ZipArchive::class) || ! extension_loaded('pdo_sqlite') || ! function_exists('proc_open')) {
        throw new RuntimeException('Use PHP 8.3+ with ZIP, pdo_sqlite (for isolated QA), and proc_open enabled to build the archive.');
    }
    if (count($argv) > 1) {
        throw new RuntimeException('Usage: composer build:prod-zip (output: build/folkscript-cpanel.zip)');
    }

    // This directory is disposable output; a failed QA run must not leave an old ZIP.
    $outputDirectory = $root.'/build';
    $legacyDirectory = $root.'/dist';
    if (is_link($outputDirectory) || (file_exists($outputDirectory) && ! is_dir($outputDirectory))) {
        throw new RuntimeException('build must be a directory, not a file or symbolic link.');
    }
    if (is_link($legacyDirectory)) {
        throw new RuntimeException('Refusing to clean legacy ZIP output through a dist symbolic link.');
    }
    echo "\nCleaning previous production build files…\n";
    removeTree($outputDirectory);
    directory($outputDirectory);
    if (is_dir($legacyDirectory)) {
        // Remove only output from earlier versions, preserving unrelated dist files.
        removeTree($legacyDirectory.'/folkscript-cpanel.zip');
        foreach (glob($legacyDirectory.'/.folkscript-cpanel-*.tmp') ?: [] as $file) {
            removeTree($file);
        }
        if (! (new FilesystemIterator($legacyDirectory))->valid()) {
            removeTree($legacyDirectory);
        }
    }

    // Keep OS/network tooling configuration, but never inherit app secrets or VITE_* values.
    $environment = [];
    foreach (['PATH', 'HOME', 'USER', 'TMPDIR', 'TMP', 'TEMP', 'SystemRoot', 'COMPOSER_HOME', 'COMPOSER_CACHE_DIR', 'SSL_CERT_FILE', 'SSL_CERT_DIR', 'CURL_CA_BUNDLE', 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'NO_PROXY', 'http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'] as $key) {
        if (($value = getenv($key)) !== false) {
            $environment[$key] = $value;
        }
    }
    $environment += [
        'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'APP_URL' => 'https://example.test', 'SEED_DEMO_CONTENT' => 'false',
        'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
        'COMPOSER_PROCESS_TIMEOUT' => '0', 'PUPPETEER_SKIP_DOWNLOAD' => 'true',
    ];
    $composerBinary = getenv('COMPOSER_BINARY');
    $composer = $composerBinary && is_file($composerBinary) ? [PHP_BINARY, $composerBinary] : ['composer'];
    run([...$composer, '--version'], $root, $environment);
    run(['node', '--version'], $root, $environment);
    run(['npm', '--version'], $root, $environment);
    run(['git', 'diff', '--check'], $root, $environment);
    run(['git', 'diff', '--cached', '--check'], $root, $environment);
    $tracked = explode("\0", trim(run(['git', 'ls-files', '-z'], $root, $environment, true), "\0"));

    $temporary = sys_get_temp_dir().'/folkscript-cpanel-'.bin2hex(random_bytes(8));
    directory($temporary, 0700);
    $stage = $temporary.'/app';
    directory($stage);
    $files = ['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js', 'phpunit.xml', 'LICENSE', 'bootstrap/app.php', 'bootstrap/providers.php', 'public/index.php', 'public/.htaccess', 'public/.well-known/security.txt', 'docs/API.md', 'docs/ASSETS.md', 'scripts/verify-production.php'];
    $directories = ['app/', 'config/', 'routes/', 'lang/', 'database/migrations/', 'database/seeders/', 'database/factories/', 'resources/views/', 'resources/css/', 'resources/js/', 'public/', 'tests/', 'scripts/'];

    echo "\nPreparing tracked source files in an isolated build directory…\n";
    foreach ($tracked as $file) {
        $selected = in_array($file, $files, true);
        foreach ($directories as $prefix) {
            $selected = $selected || str_starts_with($file, $prefix);
        }
        if (! $selected || preg_match('~(^|/)(\.env[^/]*|\.git[^/]*|\.DS_Store|auth\.json)(/|$)~', $file)
            || preg_match('~^public/(storage(/|$)|build(/|$)|hot$|fonts-manifest\.dev\.json$)~', $file)) {
            continue;
        }
        copySource($root, $file, $stage.'/'.$file);
    }
    foreach ($files as $file) {
        if (! is_file($stage.'/'.$file)) {
            throw new RuntimeException("Required source is missing or untracked: {$file}. Add new application files to Git before building.");
        }
    }
    copySource($root, 'deployment/cpanel.env.example', $stage.'/.env.example');
    copySource($root, 'docs/CPANEL.md', $stage.'/CPANEL.md');
    $writable = ['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'];
    foreach ($writable as $path) {
        directory($stage.'/'.$path);
    }

    echo "\nQA: validating dependencies and installing the locked test tools…\n";
    run([...$composer, 'validate', '--strict', '--no-check-publish'], $stage, $environment);
    run([...$composer, 'install', '--no-scripts', '--prefer-dist', '--no-interaction', '--no-progress', '--optimize-autoloader'], $stage, $environment);
    run([...$composer, 'check-platform-reqs'], $stage, $environment);
    run([...$composer, 'audit', '--locked'], $stage, $environment);
    run(['npm', 'ci', '--include=dev', '--no-audit', '--no-fund'], $stage, $environment);
    run(['npm', 'audit', '--audit-level=low'], $stage, $environment);

    echo "\nQA: checking PHP syntax…\n";
    $phpFiles = [$stage.'/artisan', $stage.'/public/index.php'];
    foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'routes', 'scripts', 'tests'] as $path) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage.'/'.$path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isFile() && $entry->getExtension() === 'php') {
                $phpFiles[] = $entry->getPathname();
            }
        }
    }
    foreach ($phpFiles as $file) {
        run([PHP_BINARY, '-l', $file], $stage, $environment, true);
    }
    echo 'PHP syntax passed for '.count($phpFiles)." files.\n";

    echo "\nQA: building frontend assets and running every JavaScript test…\n";
    run(['npm', 'run', 'build'], $stage, $environment);
    $javascriptTests = glob($stage.'/tests/JavaScript/*.test.mjs');
    if (! $javascriptTests) {
        throw new RuntimeException('No JavaScript tests were found; refusing to skip QA.');
    }
    run(['node', '--test', ...$javascriptTests], $stage, $environment);
    echo "\nQA: running the complete PHP application suite with in-memory SQLite…\n";
    $testEnvironment = array_replace($environment, ['APP_ENV' => 'testing']);
    // Dotenv reports a suppressed file-read warning in every test without this empty file.
    // All test settings come from the isolated process environment, never the local .env.
    if (file_put_contents($stage.'/.env', '') === false) {
        throw new RuntimeException('Could not prepare the empty test environment file.');
    }
    run([PHP_BINARY, 'artisan', 'package:discover', '--no-ansi'], $stage, $testEnvironment);
    run([...$composer, 'test', '--', '--compact', '--display-warnings', '--fail-on-warning', '--fail-on-risky'], $stage, $testEnvironment);
    removeTree($stage.'/.env');

    echo "\nQA passed. Preparing and verifying production-only dependencies…\n";
    run([...$composer, 'install', '--no-dev', '--no-scripts', '--prefer-dist', '--no-interaction', '--no-progress', '--optimize-autoloader'], $stage, $environment);
    run([...$composer, 'check-platform-reqs', '--no-dev'], $stage, $environment);
    $installed = json_decode(file_get_contents($stage.'/vendor/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
    $locked = json_decode(file_get_contents($stage.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
    $installedNames = array_column($installed['packages'], 'name');
    $productionNames = array_column($locked['packages'], 'name');
    sort($installedNames);
    sort($productionNames);
    if ($installed['dev'] !== false || $installedNames !== $productionNames) {
        throw new RuntimeException('The deployment dependencies do not exactly match the production lockfile.');
    }
    // Remove manifests that still reference QA-only service providers before booting production.
    removeTree($stage.'/bootstrap/cache');
    directory($stage.'/bootstrap/cache');
    run([PHP_BINARY, 'artisan', 'package:discover', '--no-ansi'], $stage, $environment);

    $manifestPath = $stage.'/public/build/manifest.json';
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
        if (empty($manifest[$entry]['file'])) {
            throw new RuntimeException("The asset build did not create {$entry}.");
        }
    }
    foreach ($manifest as $entry) {
        foreach ([$entry['file'], ...($entry['css'] ?? []), ...($entry['assets'] ?? [])] as $asset) {
            if (! is_file($stage.'/public/build/'.$asset)) {
                throw new RuntimeException("Missing compiled asset: {$asset}");
            }
        }
    }

    // Only views and font CSS are used from resources/ at runtime (including quote cards).
    // Keep the smoke checker outside the payload so it verifies the actual pruned package.
    if (! copy($stage.'/scripts/verify-production.php', $temporary.'/verify-production.php')) {
        throw new RuntimeException('Could not prepare the production smoke check.');
    }
    foreach (['node_modules', 'resources/js', 'package.json', 'package-lock.json', 'vite.config.js', 'tests', 'scripts', 'phpunit.xml', '.phpunit.result.cache', '.phpunit.cache'] as $path) {
        removeTree($stage.'/'.$path);
    }
    foreach (new FilesystemIterator($stage.'/resources/css') as $entry) {
        if ($entry->getFilename() !== 'fonts.css') {
            removeTree($entry->getPathname());
        }
    }
    foreach (['bootstrap/cache', 'storage'] as $path) {
        removeTree($stage.'/'.$path);
    }
    foreach ($writable as $path) {
        directory($stage.'/'.$path);
    }

    echo "\nQA: compiling production views/routes and checking a clean installation…\n";
    run([PHP_BINARY, 'artisan', 'view:cache', '--no-ansi'], $stage, $environment);
    run([PHP_BINARY, 'artisan', 'route:cache', '--no-ansi'], $stage, $environment);
    run([PHP_BINARY, $temporary.'/verify-production.php', $stage], $stage, $environment);
    // No QA state, temporary keys, compiled paths or test uploads may enter the ZIP.
    foreach (['bootstrap/cache', 'storage'] as $path) {
        removeTree($stage.'/'.$path);
    }
    foreach ($writable as $path) {
        directory($stage.'/'.$path);
    }

    echo "\nAll automated QA and production checks passed. Creating the ZIP…\n";

    $partial = $outputDirectory.'/.folkscript-cpanel-'.bin2hex(random_bytes(8)).'.tmp';
    $zip = new ZipArchive;
    if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('Could not create the ZIP archive.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    $count = 0;
    foreach ($iterator as $entry) {
        $name = str_replace(DIRECTORY_SEPARATOR, '/', substr($entry->getPathname(), strlen($stage) + 1));
        if ($entry->isLink() || preg_match('~(^|/)(\.git|\.svn|\.hg|node_modules)(/|$)~', $name)
            || preg_match('~^(tests|scripts|public/storage|resources/js)(/|$)|^public/hot$|^\.phpunit|^phpunit\.xml$~', $name)
            || (preg_match('~(^|/)\.env[^/]*$~', $name) && $name !== '.env.example')
            || ($entry->isFile() && (str_starts_with($name, 'storage/') || str_starts_with($name, 'bootstrap/cache/')))) {
            throw new RuntimeException("Unexpected build artifact: {$name}");
        }
        $isDirectory = $entry->isDir();
        $name .= $isDirectory ? '/' : '';
        $added = $isDirectory ? $zip->addEmptyDir($name) : $zip->addFile($entry->getPathname(), $name);
        $mode = $isDirectory ? 0040755 : (0100000 | ($entry->isExecutable() ? 0755 : 0644));
        if (! $added || ! $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, $mode << 16)) {
            throw new RuntimeException("Could not archive {$name}");
        }
        $count++;
    }
    if (! $zip->close()) {
        throw new RuntimeException('Could not finish writing the ZIP archive.');
    }
    $check = new ZipArchive;
    if ($check->open($partial, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException('ZIP integrity check failed.');
    }
    $check->close();
    $output = $outputDirectory.'/folkscript-cpanel.zip';
    if (! rename($partial, $output)) {
        throw new RuntimeException('Could not publish the completed ZIP.');
    }
    $partial = null;
    echo "\nCreated: {$output}\n";
    printf("Size: %.1f MB · %d entries\n", filesize($output) / 1048576, $count);
    echo 'SHA-256: '.hash_file('sha256', $output)."\n";
    echo "Manual setup instructions: docs/CPANEL.md (also CPANEL.md inside the ZIP).\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "\nBuild failed: {$exception->getMessage()}\nNo new deployment ZIP was published.\n");
    $failed = true;
} finally {
    if ($partial && is_file($partial)) {
        unlink($partial);
    }
    if ($temporary && is_dir($temporary)) {
        removeTree($temporary);
    }
}

exit(isset($failed) ? 1 : 0);
