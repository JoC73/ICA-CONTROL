<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
            ],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'color' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'string', 'max:40'],
        ]);

        $existing = Category::where('name', $payload['name'])
            ->where('type', $payload['type'])
            ->first();

        if ($existing && $existing->status === 'active') {
            abort(422, 'Ya existe una categoria activa con ese nombre.');
        }

        if ($existing) {
            $existing->update([
                'color' => $payload['color'] ?? $existing->color,
                'icon' => $payload['icon'] ?? $existing->icon,
                'status' => 'active',
            ]);

            return response()->json(['category' => $existing->fresh()], 200);
        }

        $category = Category::create([
            'name' => $payload['name'],
            'type' => $payload['type'],
            'color' => $payload['color'] ?? ($payload['type'] === 'income' ? '#059669' : '#dc2626'),
            'icon' => $payload['icon'] ?? 'circle',
            'status' => 'active',
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $payload = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('categories')->where(fn ($query) => $query->where('type', $request->input('type', $category->type)))->ignore($category->id),
            ],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'color' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'string', 'max:40'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ]);

        $category->update($payload);

        return response()->json(['category' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->update(['status' => 'inactive']);

        return response()->json(['message' => 'Categoria desactivada.']);
    }
}
