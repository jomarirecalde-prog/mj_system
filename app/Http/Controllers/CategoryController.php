<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
        $this->middleware('role:admin')->except(['index', 'show']);
    }

    public function index(Request $request): View
    {
        $categories = InventoryCategory::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(20);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:inventory_categories,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        InventoryCategory::query()->create($data);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function show(InventoryCategory $category): View
    {
        $category->loadCount('items');

        return view('categories.show', compact('category'));
    }

    public function edit(InventoryCategory $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name,'.$category->id],
            'code' => ['nullable', 'string', 'max:50', 'unique:inventory_categories,code,'.$category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        if ($category->items()->exists()) {
            return back()->with('error', 'Cannot delete a category that has inventory items.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
