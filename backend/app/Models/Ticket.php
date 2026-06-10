<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'user_id', 'transaction_id', 'balance_after', 'issued_at'])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'balance_after' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }
}
