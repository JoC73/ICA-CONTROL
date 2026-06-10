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
        [$from, $to] = match ($period) {
            'daily' => [CarbonImmutable::today(), CarbonImmutable::today()],
            'weekly' => [CarbonImmutable::now()->startOfWeek(), CarbonImmutable::now()->endOfWeek()],
            'monthly' => [CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth()],
            'annual' => [CarbonImmutable::now()->startOfYear(), CarbonImmutable::now()->endOfYear()],
            default => abort(404),
        };

        $query = Transaction::with('category:id,name,color')
            ->whereBetween('date', [$from, $to]);

        if ($request->user()->role === 'user') {
            $query->where('user_id', $request->user()->id);
        }

        $items = $query->get();
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
