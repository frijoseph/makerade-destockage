<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::query()->withCount('products')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true, 'is_featured' => true, 'position' => 0, 'color' => '#2a2a2a', 'style' => 'plain'])]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::query()->create($request->safe()->except(['logo_file', 'remove_logo'])
            + ['logo' => $this->syncImage($request, 'logo', 'brands', null)]);

        return redirect()->route('admin.brands.index')->with('status', "Marque « {$brand->name} » créée.");
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->safe()->except(['logo_file', 'remove_logo'])
            + ['logo' => $this->syncImage($request, 'logo', 'brands', $brand->logo)]);

        return redirect()->route('admin.brands.index')->with('status', "Marque « {$brand->name} » mise à jour.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return back()->with('status', "Marque « {$brand->name} » supprimée (ses produits restent, sans marque).");
    }
}
