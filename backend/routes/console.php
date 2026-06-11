<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:monthly {period?}', function (?string $period = null) {
    $period ??= CarbonImmutable::now()->format('Y-m');
    $from = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
    $to = $from->endOfMonth();
    $filename = "backups/ica-control-{$period}.json";

    Storage::disk('local')->put($filename, json_encode([
        'app' => 'ICA-CONTROL',
        'period' => $period,
        'generated_at' => now()->toISOString(),
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
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $this->info("Backup generated: storage/app/private/{$filename}");
})->purpose('Generate a monthly ICA-CONTROL JSON backup');
