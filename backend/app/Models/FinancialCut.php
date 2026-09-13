<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['period', 'cut_at', 'income_total', 'expense_total', 'balance', 'created_by', 'notes'])]
class FinancialCut extends Model
{
    protected function casts(): array
    {
        return [
            'cut_at' => 'datetime',
            'income_total' => 'decimal:2',
            'expense_total' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public static function latestCutAt()
    {
        return static::query()->latest('cut_at')->first()?->cut_at;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
