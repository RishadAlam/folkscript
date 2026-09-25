<?php

declare(strict_types=1);

// Internal build check: only boot a disposable package with an in-memory database.
try {
    $base = isset($argv[1]) ? realpath($argv[1]) : false;
    if (! $base || $base !== realpath(getcwd()) || is_file($base.'/.env')
        || getenv('APP_ENV') !== 'production' || getenv('DB_CONNECTION') !== 'sqlite'
        || getenv('DB_DATABASE') !== ':memory:' || ! in_array(getenv('DB_URL'), [false, ''], true)
        || getenv('CACHE_STORE') !== 'array' || getenv('SESSION_DRIVER') !== 'array'
        || getenv('QUEUE_CONNECTION') !== 'sync' || getenv('MAIL_MAILER') !== 'array') {
        throw new RuntimeException('Production QA requires the isolated build directory, no .env, in-memory SQLite, and non-network cache/session/queue/mail drivers.');
    }

    // The settings provider must not query tables before the migration bootstraps them.
    $_SERVER['argv'] = ['artisan', 'migrate'];
    require $base.'/vendor/autoload.php';
    $app = require $base.'/bootstrap/app.php';
    $console = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $console->bootstrap();
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
        throw new RuntimeException('Refusing to test against a persistent database.');
    }
    foreach (['migrate', 'db:seed'] as $command) {
        if ($console->call($command, ['--force' => true]) !== 0) {
            throw new RuntimeException($console->output());
        }
    }
    if (! $app->isProduction() || App\Models\User::count() !== 0 || App\Models\Post::count() !== 0 || Spatie\Permission\Models\Role::count() !== 5) {
        throw new RuntimeException('Production seeding must create five roles and no demo accounts or stories.');
    }

    $http = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach (['/', '/developers/api', '/login'] as $path) {
        $request = Illuminate\Http\Request::create('https://example.test'.$path);
        $response = $http->handle($request);
        if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), '/build/assets/')) {
            throw new RuntimeException("Production page {$path} failed or did not load compiled assets. See the previous QA output for the failing stage.");
        }
        $http->terminate($request, $response);
    }

    // A quote-card response exercises the runtime font CSS and font files.
    $author = App\Models\User::withoutEvents(fn () => App\Models\User::create([
        'name' => 'Package check', 'username' => 'packagecheck', 'email' => 'package@example.test',
        'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'email_verified_at' => now(),
    ]));
    $author->assignRole('author');
    $story = App\Models\Post::withoutEvents(fn () => App\Models\Post::create([
        'title' => 'Package check', 'slug' => 'package-check', 'author_id' => $author->id,
        'body_html' => '<p>A readable story deserves a careful deployment.</p>',
        'status' => 'published', 'published_at' => now()->subMinute(),
    ]));
    $request = Illuminate\Http\Request::create('/posts/'.$story->id.'/quote-card', 'POST', ['quote' => 'A readable story deserves a careful deployment.']);
    $response = $app->make(App\Http\Controllers\QuoteCardController::class)($request, $story->id);
    if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), 'data:font/woff2;base64,')) {
        throw new RuntimeException('The production quote card could not embed the runtime font assets.');
    }
    echo "Production smoke checks passed: migrations, role-only seed, public pages, API docs, assets and quote cards.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Production QA failed: '.$exception->getMessage()."\n");
    exit(1);
}
