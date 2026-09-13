<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function show(Request $request, string $period)
    {
        if ($period === 'custom') {
            $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
                'type' => ['nullable', 'in:income,expense'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
            ]);
        }

        [$from, $to] = match ($period) {
            'daily' => [CarbonImmutable::today(), CarbonImmutable::today()],
            'weekly' => [CarbonImmutable::now()->startOfWeek(), CarbonImmutable::now()->endOfWeek()],
            'monthly' => [CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth()],
            'annual' => [CarbonImmutable::now()->startOfYear(), CarbonImmutable::now()->endOfYear()],
            'custom' => [
                CarbonImmutable::parse($request->query('from')),
                CarbonImmutable::parse($request->query('to')),
            ],
            default => abort(404),
        };

        $query = Transaction::with(['user:id,name', 'category:id,name,color,icon,type', 'account:id,name,type'])
            ->whereBetween('date', [$from, $to]);

        if ($request->user()->role === 'user') {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->user()->canManageUsers() && $request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $query->when($request->filled('type'), fn ($inner) => $inner->where('type', $request->input('type')));

        $items = $query->latest('date')->get();
        $income = $items->where('type', 'income')->sum('amount');
        $expense = $items->where('type', 'expense')->sum('amount');

        return response()->json([
            'period' => $period,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'balance' => round($income - $expense, 2),
            'items' => $items->values(),
        ]);
    }
}
