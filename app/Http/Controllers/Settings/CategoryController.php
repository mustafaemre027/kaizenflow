<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCategoryRequest;
use App\Http\Requests\Settings\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        Category::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Kategori başarıyla eklendi.');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        $category->update($validated);

        return back()->with('success', 'Kategori başarıyla güncellendi.');
    }

    public function toggleStatus(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $category->is_active = ! $category->is_active;
        $category->save();

        $status = $category->is_active ? 'aktif' : 'pasif';

        return back()->with('success', "Kategori başarıyla {$status} duruma getirildi.");
    }
}
