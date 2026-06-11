<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for in-memory database feature tests.');
        }

        parent::setUp();
    }

    public function test_first_public_registration_creates_initial_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Administrador Inicial',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', User::ROLE_ADMIN);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_setup_status_reports_registration_open_when_no_users_exist(): void
    {
        $response = $this->getJson('/api/setup-status');

        $response->assertOk()
            ->assertJsonPath('registration_open', true);
    }

    public function test_public_registration_is_closed_after_a_user_exists(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->postJson('/api/register', [
            'name' => 'Usuario Externo',
            'email' => 'externo@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    }

    public function test_setup_status_reports_registration_closed_after_a_user_exists(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->getJson('/api/setup-status');

        $response->assertOk()
            ->assertJsonPath('registration_open', false);
    }

    public function test_regular_user_cannot_create_users_or_assign_roles(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/users', [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo-admin@example.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'nuevo-admin@example.com']);
    }

    public function test_admin_can_create_users_and_assign_allowed_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Operador',
            'email' => 'operador@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'operador@example.com')
            ->assertJsonPath('user.role', User::ROLE_USER);

        $this->assertDatabaseHas('users', [
            'email' => 'operador@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_admin_cannot_assign_unknown_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Rol No Valido',
            'email' => 'rol-no-valido@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'rol-no-valido@example.com']);
    }
}
