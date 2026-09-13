<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinancialCut;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class FinancialCutController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageUsers(), 403, 'Solo un administrador puede ver cortes.');

        return FinancialCut::with('creator:id,name,email,role')
            ->latest('cut_at')
            ->paginate(24);
    }

    public function current(Request $request)
    {
        $latestCut = FinancialCut::query()->latest('cut_at')->first();

        return response()->json([
            'cut' => $latestCut,
            'current_period_started_at' => $latestCut?->cut_at?->toDateTimeString(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageUsers(), 403, 'Solo un administrador puede realizar cortes.');

        $payload = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $latestCutAt = FinancialCut::latestCutAt();
        $query = Transaction::query();
        $query->when($latestCutAt, fn ($inner) => $inner->where('created_at', '>', $latestCutAt));

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');
        $cutAt = now();

        $cut = FinancialCut::create([
            'period' => CarbonImmutable::parse($cutAt)->format('Y-m'),
            'cut_at' => $cutAt,
            'income_total' => round($income, 2),
            'expense_total' => round($expense, 2),
            'balance' => round($income - $expense, 2),
            'created_by' => $request->user()->id,
            'notes' => $payload['notes'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'financial_cut.created',
            'auditable_type' => FinancialCut::class,
            'auditable_id' => $cut->id,
            'metadata' => [
                'period' => $cut->period,
                'income_total' => $cut->income_total,
                'expense_total' => $cut->expense_total,
                'balance' => $cut->balance,
                'notes' => $cut->notes,
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Corte realizado. El nuevo periodo inicia en cero.',
            'cut' => $cut->load('creator:id,name,email,role'),
            'current' => [
                'income' => 0,
                'expense' => 0,
                'balance' => 0,
            ],
        ], 201);
    }
}
