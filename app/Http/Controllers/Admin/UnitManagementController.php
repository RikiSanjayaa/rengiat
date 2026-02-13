<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitRequest;
use App\Http\Requests\Admin\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitManagementController extends Controller
{
    public function index(): Response
    {
        $units = Unit::query()
            ->ordered()
            ->get()
            ->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'label' => sprintf('Unit %d', $unit->order_index),
                'order_index' => $unit->order_index,
                'active' => $unit->active,
                'created_at' => $unit->created_at?->toDateTimeString(),
            ])
            ->values();

        return Inertia::render('admin/units/index', [
            'units' => $units,
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Unit::create([
            'name' => sprintf('Unit %d', $validated['order_index']),
            'order_index' => $validated['order_index'],
            'active' => $validated['active'],
        ]);

        return back()->with('success', 'Unit berhasil dibuat.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validated();

        $unit->update([
            'name' => sprintf('Unit %d', $validated['order_index']),
            'order_index' => $validated['order_index'],
            'active' => $validated['active'],
        ]);

        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }
}
