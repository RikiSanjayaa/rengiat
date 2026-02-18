<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ReportSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportSettingController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        $setting = ReportSetting::where('user_id', $request->user()->id)->first();

        return Inertia::render('settings/report', [
            'reportSetting' => $setting ? [
                'atas_nama' => $setting->atas_nama,
                'jabatan' => $setting->jabatan,
                'nama_penandatangan' => $setting->nama_penandatangan,
                'pangkat_nrp' => $setting->pangkat_nrp,
            ] : [
                'atas_nama' => '',
                'jabatan' => '',
                'nama_penandatangan' => '',
                'pangkat_nrp' => '',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'atas_nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'nama_penandatangan' => ['required', 'string', 'max:255'],
            'pangkat_nrp' => ['required', 'string', 'max:255'],
        ]);

        ReportSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return back()->with('status', 'report-settings-updated');
    }
}
