<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('products')
                ->withSum('products as stock', 'stock')
                ->orderBy('position')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category([
                'is_active' => true,
                'bg' => '#f1f1f1',
                'position' => (int) Category::query()->max('position') + 1,
            ]),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        return redirect()->route('admin.categories.index')->with('status', "Catégorie « {$category->name} » créée.");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')->with('status', "Catégorie « {$category->name} » mise à jour.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => "« {$category->name} » contient encore des produits : déplacez-les ou désactivez la catégorie."]);
        }

        $category->delete();

        return back()->with('status', "Catégorie « {$category->name} » supprimée.");
    }
}
