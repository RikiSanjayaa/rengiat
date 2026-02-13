<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_super_admin_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $target = User::factory()->create([
            'role' => UserRole::Viewer,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'role' => UserRole::SuperAdmin->value,
                'subdit_id' => null,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertForbidden();
    }
}
