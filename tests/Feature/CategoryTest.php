<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_list_categories(): void
    {
        $user = User::factory()->create();

        Category::create([
            'name' => 'Food',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
    }

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/categories', [
            'name' => 'Travel',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('categories', [
            'name' => 'Travel',
        ]);
    }

    public function test_user_can_update_category(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Food',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            "/api/categories/{$category->id}",
            [
                'name' => 'Travel',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Travel',
        ]);
    }

    public function test_user_can_delete_category(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Food',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/categories/{$category->id}"
        );

        $response->assertOk();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/categories',
            [
                'name' => '',
            ]
        );

        $response->assertStatus(422);
    }
}