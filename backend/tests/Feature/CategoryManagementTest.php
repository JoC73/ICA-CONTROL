<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for in-memory database feature tests.');
        }

        parent::setUp();
    }

    public function test_user_can_update_financial_motive(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Donaciones',
            'type' => 'income',
            'color' => '#059669',
            'icon' => 'gift',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Donaciones especiales',
            'type' => 'income',
            'color' => '#047857',
        ]);

        $response->assertOk()
            ->assertJsonPath('category.name', 'Donaciones especiales')
            ->assertJsonPath('category.status', 'active');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Donaciones especiales',
            'status' => 'active',
        ]);
    }

    public function test_delete_deactivates_category_instead_of_removing_it(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Ofrendas',
            'type' => 'income',
            'color' => '#16a34a',
            'icon' => 'heart',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Categoria desactivada.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Ofrendas',
            'status' => 'inactive',
        ]);
    }

    public function test_creating_inactive_category_reactivates_it(): void
    {
        $user = User::factory()->create();
        Category::create([
            'name' => 'Diezmos',
            'type' => 'income',
            'color' => '#0d9488',
            'icon' => 'landmark',
            'status' => 'inactive',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/categories', [
            'name' => 'Diezmos',
            'type' => 'income',
            'color' => '#0891b2',
        ]);

        $response->assertOk()
            ->assertJsonPath('category.name', 'Diezmos')
            ->assertJsonPath('category.status', 'active');

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', [
            'name' => 'Diezmos',
            'type' => 'income',
            'status' => 'active',
        ]);
    }
}
