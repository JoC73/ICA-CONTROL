<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;

class CatalogController extends Controller
{
    public function __invoke()
    {
        $this->ensureDefaultAccounts();
        $this->ensureDefaultCategories();

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

    private function ensureDefaultAccounts(): void
    {
        if (Account::where('status', 'active')->exists()) {
            return;
        }

        foreach ([
            ['Caja principal', 'cash'],
            ['Cuenta bancaria', 'bank'],
        ] as [$name, $type]) {
            Account::updateOrCreate(
                ['name' => $name],
                ['type' => $type, 'initial_balance' => 0, 'status' => 'active']
            );
        }
    }

    private function ensureDefaultCategories(): void
    {
        if (Category::where('status', 'active')->exists()) {
            return;
        }

        foreach ([
            ['Ofrendas', 'income', '#16a34a', 'heart'],
            ['Donaciones', 'income', '#059669', 'gift'],
            ['Diezmos', 'income', '#0d9488', 'landmark'],
            ['Ventas', 'income', '#2563eb', 'shopping-bag'],
            ['Servicios', 'income', '#0284c7', 'briefcase'],
            ['Combustible', 'expense', '#dc2626', 'fuel'],
            ['Alimentacion', 'expense', '#ea580c', 'utensils'],
            ['Ayuda social', 'expense', '#be123c', 'hand-heart'],
            ['Salarios', 'expense', '#9333ea', 'users'],
            ['Mantenimiento', 'expense', '#4f46e5', 'wrench'],
            ['Servicios basicos', 'expense', '#64748b', 'receipt'],
        ] as [$name, $type, $color, $icon]) {
            Category::updateOrCreate(
                ['name' => $name, 'type' => $type],
                ['color' => $color, 'icon' => $icon, 'status' => 'active']
            );
        }
    }
}
