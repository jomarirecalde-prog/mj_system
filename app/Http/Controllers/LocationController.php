<?php

namespace App\Http\Controllers;

use App\Models\InventoryLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
        $this->middleware('role:admin')->except(['index', 'show']);
    }

    public function index(Request $request): View
    {
        $locations = InventoryLocation::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(20);

        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:inventory_locations,code'],
            'building' => ['nullable', 'string', 'max:255'],
            'office' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        InventoryLocation::query()->create($data);

        return redirect()->route('locations.index')->with('success', 'Location created.');
    }

    public function show(InventoryLocation $location): View
    {
        return view('locations.show', compact('location'));
    }

    public function edit(InventoryLocation $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, InventoryLocation $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:inventory_locations,code,'.$location->id],
            'building' => ['nullable', 'string', 'max:255'],
            'office' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $location->update($data);

        return redirect()->route('locations.index')->with('success', 'Location updated.');
    }

    public function destroy(InventoryLocation $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Location deleted.');
    }
}
