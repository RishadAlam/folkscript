<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase { refreshDatabase as protected refreshIsolatedDatabase; }

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \LogicException('Security tests require an isolated in-memory SQLite database.');
        }
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Notification::fake();
        config(['scout.driver' => null]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['reader', 'author', 'editor', 'admin', 'super-admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    protected function account(string $role = 'reader', array $attributes = []): User
    {
        $user = User::factory()->create(['username' => fake()->unique()->bothify('person_########'), 'password' => 'SecurePassword123', ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    protected function confirmPassword(): static
    {
        return $this->withSession(['auth.password_confirmed_at' => time()]);
    }
}
