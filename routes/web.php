<?php

use App\Http\Controllers\Admin\UnitManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Rengiat\DailyInputController;
use App\Http\Controllers\Rengiat\RengiatEntryController;
use App\Http\Controllers\Rengiat\ReportGeneratorController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('daily-input', [DailyInputController::class, 'index'])->name('daily-input.index');

    Route::post('entries', [RengiatEntryController::class, 'store'])->name('entries.store');
    Route::put('entries/{rengiatEntry}', [RengiatEntryController::class, 'update'])->name('entries.update');
    Route::delete('entries/{rengiatEntry}', [RengiatEntryController::class, 'destroy'])->name('entries.destroy');

    Route::get('reports', [ReportGeneratorController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportGeneratorController::class, 'export'])->name('reports.export');

    Route::middleware('can:manage-users')->group(function () {
        Route::get('admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::put('admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    });

    Route::middleware('can:manage-units')->group(function () {
        Route::get('admin/units', [UnitManagementController::class, 'index'])->name('admin.units.index');
        Route::post('admin/units', [UnitManagementController::class, 'store'])->name('admin.units.store');
        Route::put('admin/units/{unit}', [UnitManagementController::class, 'update'])->name('admin.units.update');
        Route::delete('admin/units/{unit}', [UnitManagementController::class, 'destroy'])->name('admin.units.destroy');
    });
});

require __DIR__.'/settings.php';
