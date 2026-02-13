<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User|null $actor */
        $actor = $request->user();

        return Inertia::render('admin/users/index', [
            'users' => User::query()
                ->with('unit:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit?->name,
                    'created_at' => $user->created_at?->toDateTimeString(),
                    'can_edit' => $actor?->can('manage-users') && $this->canManageTargetUser($actor, $user),
                    'can_delete' => $actor?->can('manage-users') && ! $actor->is($user) && $this->canManageTargetUser($actor, $user),
                ])
                ->values(),
            'units' => Unit::query()
                ->active()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ])
                ->values(),
            'roles' => collect(UserRole::cases())
                ->filter(fn (UserRole $role): bool => $actor?->isSuperAdmin() || $role !== UserRole::SuperAdmin)
                ->map(fn (UserRole $role) => [
                    'value' => $role->value,
                    'label' => str($role->value)->replace('_', ' ')->title()->toString(),
                ])
                ->values(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User|null $actor */
        $actor = $request->user();

        abort_if(! $actor, 403);

        if (! $this->canAssignRole($actor, $validated['role'])) {
            abort(403);
        }

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'unit_id' => $validated['role'] === UserRole::Operator->value
                ? $validated['unit_id']
                : null,
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'User berhasil dibuat.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User|null $actor */
        $actor = $request->user();

        abort_if(! $actor, 403);

        if (! $this->canManageTargetUser($actor, $user)) {
            abort(403);
        }

        if (! $this->canAssignRole($actor, $validated['role'])) {
            abort(403);
        }

        $this->guardAgainstRemovingLastSuperAdmin($actor, $user, $validated['role']);

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'unit_id' => $validated['role'] === UserRole::Operator->value
                ? $validated['unit_id']
                : null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = request()->user();

        abort_if(! $actor, 403);

        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'User login saat ini tidak bisa dihapus.',
            ]);
        }

        if (! $this->canManageTargetUser($actor, $user)) {
            abort(403);
        }

        $this->guardAgainstRemovingLastSuperAdmin($actor, $user, null);

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    private function guardAgainstRemovingLastSuperAdmin(User $actor, User $target, ?string $nextRole): void
    {
        if (! $actor->isSuperAdmin()) {
            return;
        }

        if (! $target->isSuperAdmin()) {
            return;
        }

        $willRemainSuperAdmin = $nextRole === UserRole::SuperAdmin->value;

        if ($willRemainSuperAdmin) {
            return;
        }

        $remainingSuperAdminCount = User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->whereKeyNot($target->id)
            ->count();

        if ($remainingSuperAdminCount === 0) {
            throw ValidationException::withMessages([
                'role' => 'Minimal harus ada satu super admin aktif.',
            ]);
        }
    }

    private function canManageTargetUser(User $actor, User $target): bool
    {
        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    private function canAssignRole(User $actor, string $role): bool
    {
        if ($role === UserRole::SuperAdmin->value && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }
}
