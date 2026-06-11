<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'category_id',
    'account_id',
    'type',
    'amount',
    'description',
    'payment_method',
    'date',
    'receipt_path',
    'provider',
    'notes',
    'delete_reason',
    'deleted_by',
])]
class Transaction extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
