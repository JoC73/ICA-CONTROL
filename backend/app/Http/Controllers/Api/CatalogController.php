<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;

class CatalogController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'categories' => Category::where('status', 'active')->orderBy('type')->orderBy('name')->get(),
            'accounts' => Account::where('status', 'active')->orderBy('name')->get(),
            'payment_methods' => [
                ['id' => 'cash', 'name' => 'Efectivo'],
                ['id' => 'transfer', 'name' => 'Transferencia'],
                ['id' => 'card', 'name' => 'Tarjeta'],
                ['id' => 'check', 'name' => 'Cheque'],
                ['id' => 'deposit', 'name' => 'Deposito'],
                ['id' => 'mobile_payment', 'name' => 'Pago movil'],
            ],
        ]);
    }
}
