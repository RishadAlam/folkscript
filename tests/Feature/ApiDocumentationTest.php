<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Support\Facades\Route;

class ApiDocumentationTest extends SecurityTestCase
{
    public function test_public_reference_and_markdown_share_the_complete_guide(): void
    {
        $this->get('/developers/api')->assertOk()
            ->assertSee('API reference')
            ->assertSee('id="authentication-and-tokens"', false)
            ->assertSee('href="#errors-and-rate-limits"', false)
            ->assertSee('Download OpenAPI')
            ->assertSee('tokens:manage')
            ->assertSee('tabindex="0" aria-label="Code example"', false);

        $this->get('/developers/api.md')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertContent(file_get_contents(base_path('docs/API.md')));
    }

    public function test_openapi_documents_every_registered_v1_operation_and_resolves_schema_references(): void
    {
        $spec = json_decode(file_get_contents(public_path('openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $actual = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $actual[] = strtolower($method).' /'.substr($route->uri(), strlen('api/v1/'));
            }
        }
        $documented = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $documented[] = $method.' '.$path;
                $this->assertArrayHasKey('200', $operation['responses']);
                $this->assertArrayHasKey('429', $operation['responses']);
            }
        }
        sort($actual);
        sort($documented);
        $this->assertSame($actual, $documented);
        $this->assertSame('/api/v1', $spec['servers'][0]['url']);
        $this->assertSame('http', $spec['components']['securitySchemes']['bearerAuth']['type']);

        $walk = function (array $value) use (&$walk, $spec): void {
            foreach ($value as $key => $child) {
                if ($key === '$ref') {
                    $target = $spec;
                    foreach (explode('/', substr($child, 2)) as $segment) {
                        $this->assertArrayHasKey($segment, $target, 'Unresolved OpenAPI reference: '.$child);
                        $target = $target[$segment];
                    }
                } elseif (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($spec);
    }

    public function test_documented_bearer_scopes_and_personal_response_work_without_session_authentication(): void
    {
        $user = $this->account('author', ['bio' => null]);
        $own = Post::create(['author_id' => $user->id, 'title' => 'Private export', 'slug' => 'private-export', 'status' => 'draft', 'body_html' => '<p>A private draft.</p>', 'body_json' => ['type' => 'doc', 'content' => []]]);
        Post::create(['author_id' => $this->account('author')->id, 'title' => 'Another draft', 'slug' => 'another-draft', 'status' => 'draft']);
        $token = $user->createToken('Documentation client', ['profile:read', 'posts:read'], now()->addDays(90));

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->get('/api/v1/me')->assertOk()->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.email', $user->email)->assertJsonPath('data.bio', null)
            ->assertJsonMissingPath('data.roles')->assertJsonMissingPath('data.password');
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/me/posts?page=1')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)->assertJsonPath('data.0.status', 'draft')
            ->assertJsonPath('data.0.body_json.type', 'doc')->assertJsonMissingPath('links')
            ->assertJsonPath('meta.total', 1);
        app('auth')->forgetGuards();
        $this->deleteJson('/api/v1/tokens/'.$token->accessToken->id)->assertForbidden();
    }

    public function test_documented_public_empty_pagination_and_json_validation_contract(): void
    {
        $this->get('/api/v1/posts')->assertOk()->assertJsonPath('data', [])
            ->assertJsonPath('links.next', null)->assertJsonPath('links.previous', null)
            ->assertJsonPath('meta.current_page', 1)->assertJsonPath('meta.last_page', 1)->assertJsonPath('meta.total', 0);
        $this->get('/api/v1/posts?per_page=51')->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors('per_page');
        $this->get('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_authenticated_rate_limit_is_shared_per_account_and_public_limit_is_separate(): void
    {
        $user = $this->account();
        $one = $user->createToken('First client', ['profile:read'])->plainTextToken;
        $two = $user->createToken('Second client', ['profile:read'])->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$one)->getJson('/api/v1/me')->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60')->assertHeader('X-RateLimit-Remaining', '59');
        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$two)->getJson('/api/v1/me')->assertOk()
            ->assertHeader('X-RateLimit-Remaining', '58');
        app('auth')->forgetGuards();
        // A real request starts with the web default; the test container is reused.
        app('auth')->shouldUse('web');
        $this->getJson('/api/v1/posts')->assertOk()->assertHeader('X-RateLimit-Remaining', '59');
    }
}
