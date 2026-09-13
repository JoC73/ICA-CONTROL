<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialCut;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialCutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for in-memory database feature tests.');
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_cut_and_current_period_starts_empty(): void
    {
        [$admin, $category, $account] = $this->baseRecords();
        Sanctum::actingAs($admin);

        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->transaction($admin, $category, $account, 'income', 500, 'Ingreso antes del corte');
        $this->transaction($admin, $category, $account, 'expense', 125, 'Egreso antes del corte');

        $response = $this->postJson('/api/financial-cuts', [
            'notes' => 'Cierre mensual de septiembre',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Corte realizado. El nuevo periodo inicia en cero.')
            ->assertJsonPath('cut.income_total', '500.00')
            ->assertJsonPath('cut.expense_total', '125.00')
            ->assertJsonPath('cut.balance', '375.00');

        $this->assertDatabaseHas('financial_cuts', [
            'period' => '2026-09',
            'balance' => 375,
        ]);

        $currentResponse = $this->getJson('/api/transactions');

        $currentResponse->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_transactions_default_to_current_period_but_history_can_be_requested(): void
    {
        [$admin, $category, $account] = $this->baseRecords();
        Sanctum::actingAs($admin);

        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->transaction($admin, $category, $account, 'income', 200, 'Ingreso cerrado');
        FinancialCut::create([
            'period' => '2026-09',
            'cut_at' => now(),
            'income_total' => 200,
            'expense_total' => 0,
            'balance' => 200,
            'created_by' => $admin->id,
        ]);

        Carbon::setTestNow('2026-09-10 11:00:00');
        $this->transaction($admin, $category, $account, 'income', 75, 'Ingreso nuevo periodo');

        $currentResponse = $this->getJson('/api/transactions');
        $historyResponse = $this->getJson('/api/transactions?current_period=0');

        $currentResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Ingreso nuevo periodo');

        $historyResponse->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_custom_report_keeps_historical_range_after_cut(): void
    {
        [$admin, $category, $account] = $this->baseRecords();
        Sanctum::actingAs($admin);

        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->transaction($admin, $category, $account, 'income', 400, 'Ingreso historico');
        FinancialCut::create([
            'period' => '2026-09',
            'cut_at' => now(),
            'income_total' => 400,
            'expense_total' => 0,
            'balance' => 400,
            'created_by' => $admin->id,
        ]);

        Carbon::setTestNow('2026-09-11 09:00:00');
        $this->transaction($admin, $category, $account, 'expense', 150, 'Egreso nuevo');

        $response = $this->getJson('/api/reports/custom?from=2026-09-01&to=2026-09-30');

        $response->assertOk()
            ->assertJsonPath('income', 400)
            ->assertJsonPath('expense', 150)
            ->assertJsonPath('balance', 250)
            ->assertJsonCount(2, 'items');
    }

    private function baseRecords(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create([
            'name' => 'Ofrendas',
            'type' => 'income',
            'color' => '#16a34a',
            'icon' => 'heart',
            'status' => 'active',
        ]);
        $account = Account::create([
            'name' => 'Caja principal',
            'type' => 'cash',
            'initial_balance' => 0,
            'status' => 'active',
        ]);

        return [$admin, $category, $account];
    }

    private function transaction(User $user, Category $category, Account $account, string $type, int $amount, string $description): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'account_id' => $account->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'payment_method' => 'cash',
            'date' => now()->toDateString(),
        ]);
    }
}
