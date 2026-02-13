<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_settings_page_is_unavailable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/two-factor')
            ->assertNotFound();
    }
}
