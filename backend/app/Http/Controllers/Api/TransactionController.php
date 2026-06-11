<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user:id,name', 'category:id,name,color,icon,type', 'account:id,name,type'])
            ->latest('date');

        if ($request->user()->role === 'user') {
            $query->where('user_id', $request->user()->id);
        }

        foreach (['type', 'category_id', 'user_id', 'account_id'] as $filter) {
            $query->when($request->filled($filter), fn ($q) => $q->where($filter, $request->input($filter)));
        }

        $query->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->input('from')));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->input('to')));
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = '%'.$request->input('search').'%';
            $q->where(fn ($inner) => $inner->where('description', 'like', $search)->orWhere('provider', 'like', $search));
        });

        return $query->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $transaction = Transaction::create($data + ['user_id' => $request->user()->id]);

        $balance = Transaction::sum(DB::raw("case when type = 'income' then amount else amount * -1 end"));
        $ticket = Ticket::create([
            'code' => 'TK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => $request->user()->id,
            'transaction_id' => $transaction->id,
            'balance_after' => $balance,
            'issued_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'transaction.created',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'metadata' => ['ticket' => $ticket->code],
            'ip_address' => $request->ip(),
        ]);

        return response()->json($transaction->load(['category', 'account', 'user']), 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);
        $before = $transaction->only(['type', 'amount', 'description', 'category_id', 'account_id', 'payment_method', 'date', 'provider', 'notes']);
        $data = $this->validated($request);
        unset($data['change_reason']);
        $transaction->update($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'transaction.updated',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'metadata' => [
                'reason' => $request->input('change_reason'),
                'before' => $before,
                'after' => $transaction->only(['type', 'amount', 'description', 'category_id', 'account_id', 'payment_method', 'date', 'provider', 'notes']),
            ],
            'ip_address' => $request->ip(),
        ]);

        return $transaction->load(['category', 'account', 'user']);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_if($request->user()->role === 'user', 403, 'No tienes permiso para eliminar movimientos.');
        $payload = $request->validate([
            'delete_reason' => ['required', 'string', 'min:6', 'max:500'],
        ]);

        $transaction->forceFill([
            'delete_reason' => $payload['delete_reason'],
            'deleted_by' => $request->user()->id,
        ])->save();

        $transaction->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'transaction.deleted',
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'metadata' => [
                'reason' => $payload['delete_reason'],
                'snapshot' => $transaction->only(['type', 'amount', 'description', 'category_id', 'account_id', 'payment_method', 'date', 'provider', 'notes']),
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Movimiento eliminado']);
    }

    public function history(Request $request)
    {
        abort_if($request->user()->role === 'user', 403, 'No tienes permiso para ver el historial completo.');

        return AuditLog::with('user:id,name,email,role')
            ->where('auditable_type', Transaction::class)
            ->latest()
            ->paginate(50);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'description' => ['required', 'string', 'max:180'],
            'payment_method' => ['required', 'in:cash,transfer,card,check,deposit,mobile_payment'],
            'date' => ['required', 'date'],
            'receipt_path' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function authorizeOwner(Request $request, Transaction $transaction): void
    {
        abort_if($request->user()->role === 'user' && $transaction->user_id !== $request->user()->id, 403);
    }
}
