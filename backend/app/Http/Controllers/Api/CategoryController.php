<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories')->where(fn ($query) => $query->where('type', $request->input('type'))),
            ],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'color' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'string', 'max:40'],
        ]);

        $category = Category::create([
            'name' => $payload['name'],
            'type' => $payload['type'],
            'color' => $payload['color'] ?? ($payload['type'] === 'income' ? '#059669' : '#dc2626'),
            'icon' => $payload['icon'] ?? 'circle',
            'status' => 'active',
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function destroy(Category $category): JsonResponse
    {
        $hasTransactions = Transaction::where('category_id', $category->id)->exists();

        abort_if($hasTransactions, 422, 'No se puede eliminar una categoria con movimientos registrados.');

        $category->delete();

        return response()->json(['message' => 'Categoria eliminada.']);
    }
}
