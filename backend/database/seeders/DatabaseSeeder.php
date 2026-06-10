<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@appfinanzas.test'], [
            'name' => 'Administrador',
            'password' => Hash::make('ControlSeguro2026'),
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        foreach ([
            ['Caja principal', 'cash', 0],
            ['Cuenta bancaria', 'bank', 0],
            ['Caja chica', 'cash', 0],
            ['Cuenta ahorro', 'savings', 0],
        ] as [$name, $type, $initial]) {
            Account::updateOrCreate(['name' => $name], ['type' => $type, 'initial_balance' => $initial, 'status' => 'active']);
        }

        $categories = [
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
        ];

        foreach ($categories as [$name, $type, $color, $icon]) {
            Category::updateOrCreate(
                ['name' => $name, 'type' => $type],
                ['color' => $color, 'icon' => $icon, 'status' => 'active']
            );
        }
    }
}
