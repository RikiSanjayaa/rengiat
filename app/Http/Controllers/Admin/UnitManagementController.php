<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitRequest;
use App\Http\Requests\Admin\UpdateUnitRequest;
use App\Models\Subdit;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitManagementController extends Controller
{
    public function index(): Response
    {
        $units = Unit::query()
            ->with('subdit:id,name')
            ->ordered()
            ->get()
            ->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'subdit_id' => $unit->subdit_id,
                'subdit_name' => $unit->subdit?->name,
                'name' => $unit->name,
                'order_index' => $unit->order_index,
                'active' => $unit->active,
                'created_at' => $unit->created_at?->toDateTimeString(),
            ])
            ->values();

        return Inertia::render('admin/units/index', [
            'units' => $units,
            'subdits' => Subdit::query()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Subdit $subdit) => [
                    'id' => $subdit->id,
                    'name' => $subdit->name,
                ])
                ->values(),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return back()->with('success', 'Unit berhasil dibuat.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }
}
