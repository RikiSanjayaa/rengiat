<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user()?->loadMissing('unit:id,name', 'subdit:id,name');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => null,
                    'role' => $user->role?->value,
                    'subdit_id' => $user->subdit_id,
                    'subdit_name' => $user->subdit?->name,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit?->name,
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                ] : null,
                'abilities' => [
                    'manage_users' => $user?->can('manage-users') ?? false,
                    'manage_units' => $user?->can('manage-units') ?? false,
                    'export_rengiat' => $user?->can('export-rengiat') ?? false,
                    'view_audit_logs' => $user?->can('view-audit-logs') ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
