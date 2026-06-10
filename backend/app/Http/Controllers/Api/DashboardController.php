<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = CarbonImmutable::now();
        $base = Transaction::query();

        if ($request->user()->role === 'user') {
            $base->where('user_id', $request->user()->id);
        }

        $income = (clone $base)->where('type', 'income')->sum('amount');
        $expense = (clone $base)->where('type', 'expense')->sum('amount');
        $monthIncome = (clone $base)->where('type', 'income')->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()])->sum('amount');
        $monthExpense = (clone $base)->where('type', 'expense')->whereBetween('date', [$now->startOfMonth(), $now->endOfMonth()])->sum('amount');

        $monthly = (clone $base)
            ->selectRaw("to_char(date, 'YYYY-MM') as period")
            ->selectRaw("sum(case when type = 'income' then amount else 0 end) as income")
            ->selectRaw("sum(case when type = 'expense' then amount else 0 end) as expense")
            ->groupBy('period')
            ->orderBy('period')
            ->limit(12)
            ->get();

        $topCategories = (clone $base)
            ->where('type', 'expense')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->select('categories.name', 'categories.color', DB::raw('sum(transactions.amount) as total'))
            ->groupBy('categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'balance' => round($income - $expense, 2),
            'income_total' => round($income, 2),
            'expense_total' => round($expense, 2),
            'month_income' => round($monthIncome, 2),
            'month_expense' => round($monthExpense, 2),
            'monthly' => $monthly,
            'top_categories' => $topCategories,
            'low_balance_alert' => ($income - $expense) < 500,
            'categories_count' => Category::where('status', 'active')->count(),
        ]);
    }
}
