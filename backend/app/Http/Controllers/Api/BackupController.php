<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function monthly(Request $request): JsonResponse
    {
        abort_unless($request->user()->canManageUsers(), 403, 'Solo un administrador puede generar backups.');

        $period = $request->input('period', CarbonImmutable::now()->format('Y-m'));
        $from = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
        $to = $from->endOfMonth();

        $payload = [
            'app' => 'ICA-CONTROL',
            'period' => $period,
            'generated_at' => now()->toISOString(),
            'generated_by' => $request->user()->only(['id', 'name', 'email', 'role']),
            'users' => User::select('id', 'name', 'email', 'role', 'status', 'created_at')->get(),
            'accounts' => Account::all(),
            'categories' => Category::all(),
            'transactions' => Transaction::withTrashed()
                ->with(['user:id,name,email,role', 'category:id,name,type,color,icon', 'account:id,name,type'])
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('date')
                ->get(),
            'tickets' => Ticket::whereBetween('issued_at', [$from->startOfDay(), $to->endOfDay()])->get(),
            'audit_logs' => AuditLog::with('user:id,name,email,role')
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->orderBy('created_at')
                ->get(),
        ];

        $filename = "backups/ica-control-{$period}.json";
        Storage::disk('local')->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'message' => 'Backup mensual generado.',
            'period' => $period,
            'filename' => $filename,
            'records' => [
                'transactions' => $payload['transactions']->count(),
                'audit_logs' => $payload['audit_logs']->count(),
                'users' => $payload['users']->count(),
            ],
            'backup' => $payload,
        ]);
    }
}
