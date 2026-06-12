<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;
    private $user,$category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->category = Category::create([
            'name' => 'Food'
        ]);
    }
    /**
     * A basic feature test example.
     */
    public function test_can_create_transaction(): void
    {
        $response = $this->postJson(
            '/api/transaction',
            [
                'title' => 'KFC',
                'amount' => 120,
                'type' => 'expense',
                'category_id' => $this->category->id
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'transactions',
            [
                'title' => 'KFC',
                'amount' => 120,
                'user_id' => $this->user->id
            ]
        );
    }

    public function test_title_is_required(): void
    {
        $response = $this->postJson(
            '/api/transaction',
            [
                'amount' => 120,
                'type' => 'expense',
                'category_id' => $this->category->id
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title'
        ]);
    }

    public function test_amount_is_required(): void
    {
        $response = $this->postJson(
            '/api/transaction',
            [
                'title' => 'KFC',
                'type' => 'expense',
                'category_id' => $this->category->id
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'amount'
        ]);
    }

    public function test_type_must_be_valid(): void
    {
        $response = $this->postJson(
            '/api/transaction',
            [
                'title' => 'KFC',
                'amount' => 120,
                'type' => 'abc',
                'category_id' => $this->category->id
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'type'
        ]);
    }

    public function test_user_cannot_view_other_user_transaction(): void
    {
        $userA = User::factory()->create();

        $transaction = Transaction::create([
            'user_id' => $userA->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $userB = User::factory()->create();

        Sanctum::actingAs($userB);

        $response = $this->getJson(
            "/api/transaction/{$transaction->id}"
        );

        $response->assertStatus(404);
    }

    public function test_can_delete_transaction(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->deleteJson(
            "/api/transaction/{$transaction->id}"
        );

        $response->assertOk();

        $this->assertDatabaseMissing(
            'transactions',
            [
                'id' => $transaction->id
            ]
        );
    }

    public function test_can_update_transaction(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->putJson(
            "/api/transaction/{$transaction->id}",
            [
                'title' => 'McDonald',
                'amount' => 199,
                'type' => 'expense',
                'category_id' => $this->category->id
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
                'title' => 'McDonald',
                'amount' => 199,
                'type' => 'expense',
                'category_id' => $this->category->id
            ]
        );
    }

    public function test_can_search_transaction(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Pizza',
            'amount' => 250,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/transaction?search=KFC');

        $response->assertOk();

        $response->assertJsonFragment([
            'title' => 'KFC'
        ]);

        $response->assertJsonMissing([
            'title' => 'Pizza'
        ]);
    }

    public function test_can_filter_transaction_by_type(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Salary',
            'amount' => 30000,
            'type' => 'income',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/transaction?type=income');

        $response->assertOk();

        $response->assertJsonFragment([
            'title' => 'Salary'
        ]);

        $response->assertJsonMissing([
            'title' => 'KFC'
        ]);
    }

    public function test_can_paginate_transactions(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Transaction::create([
                'user_id' => $this->user->id,
                'title' => 'Transaction ' . $i,
                'amount' => 100,
                'type' => 'expense',
                'category_id' => $this->category->id
            ]);
        }

        $response = $this->getJson('/api/transaction?per_page=5');

        $response->assertOk();

        $response->assertJsonPath('meta.per_page', 5);
    }

    public function test_can_get_dashboard_summary(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Salary',
            'amount' => 30000,
            'type' => 'income',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Coffee',
            'amount' => 80,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();

        $response->assertJson([
            'income' => 30000,
            'expense' => 200,
            'balance' => 29800
        ]);
    }

    public function test_dashboard_summary_only_counts_current_user_transactions(): void
    {
        $otherUser = User::factory()->create();

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'My Salary',
            'amount' => 30000,
            'type' => 'income',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Salary',
            'amount' => 99999,
            'type' => 'income',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();

        $response->assertJson([
            'income' => 30000,
            'expense' => 0,
            'balance' => 30000
        ]);
    }

    public function test_can_get_monthly_dashboard(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Salary',
            'amount' => 30000,
            'type' => 'income',
            'category_id' => $this->category->id,
            'created_at' => '2026-06-01'
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id,
            'created_at' => '2026-06-05'
        ]);

        $response = $this->getJson(
            '/api/dashboard/monthly'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'month' => '2026-06',
            'income' => 30000,
            'expense' => 120
        ]);
    }

    public function test_can_get_category_dashboard(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Pizza',
            'amount' => 180,
            'type' => 'expense',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson(
            '/api/dashboard/category'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'category' => 'Food',
            'total' => 300
        ]);
    }

    public function test_can_get_categories(): void
    {
        Category::create([
            'name' => 'Food'
        ]);

        Category::create([
            'name' => 'Travel'
        ]);

        $response = $this->getJson(
            '/api/categories'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'name' => 'Food'
        ]);

        $response->assertJsonFragment([
            'name' => 'Travel'
        ]);
    }

    public function test_can_logout(): void
    {
        $response = $this->postJson(
            '/api/logout'
        );

        $response->assertOk();

        $response->assertJson([
            'message' => 'Logout Success'
        ]);
    }

    public function test_can_export_transactions_csv(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Salary',
            'amount' => 30000,
            'type' => 'income',
            'category_id' => $this->category->id
        ]);

        $response = $this->get(
            '/api/transaction/export'
        );

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );
    }

    public function test_can_bulk_create_transactions(): void
    {
        $response = $this->postJson(
            '/api/transaction/bulk',
            [
                [
                    'title' => 'Salary',
                    'amount' => 30000,
                    'type' => 'income',
                    'category_id' => $this->category->id
                ],
                [
                    'title' => 'KFC',
                    'amount' => 120,
                    'type' => 'expense',
                    'category_id' => $this->category->id
                ]
            ]
        );

        $response->assertCreated();

        $response->assertJson([
            'message' => 'Bulk Create Success',
            'count' => 2
        ]);

        $this->assertDatabaseHas('transactions', [
            'title' => 'Salary',
            'amount' => 30000,
            'type' => 'income',
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('transactions', [
            'title' => 'KFC',
            'amount' => 120,
            'type' => 'expense',
            'user_id' => $this->user->id
        ]);
    }

    public function test_bulk_create_requires_valid_items(): void
    {
        $response = $this->postJson(
            '/api/transaction/bulk',
            [
                [
                    'title' => 'Salary',
                    'amount' => 30000,
                    'type' => 'income',
                    'category_id' => $this->category->id
                ],
                [
                    'title' => '',
                    'amount' => 120,
                    'type' => 'wrong-type',
                    'category_id' => 999
                ]
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            '1.title',
            '1.type',
            '1.category_id'
        ]);
    }
}