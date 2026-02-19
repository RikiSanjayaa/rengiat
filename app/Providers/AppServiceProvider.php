<?php

namespace App\Providers;

use App\Models\RengiatEntry;
use App\Models\Unit;
use App\Models\User;
use App\Observers\RengiatEntryObserver;
use App\Observers\UnitObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();

        RengiatEntry::observe(RengiatEntryObserver::class);
        User::observe(UserObserver::class);
        Unit::observe(UnitObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(8));
    }

    protected function configureAuthorization(): void
    {
        Gate::define('export-rengiat', fn (User $user): bool => $user->canExportRengiat());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdminLike());
        Gate::define('manage-units', fn (User $user): bool => $user->isAdminLike());
        Gate::define('view-audit-logs', fn (User $user): bool => $user->isAdminLike());
    }
}
