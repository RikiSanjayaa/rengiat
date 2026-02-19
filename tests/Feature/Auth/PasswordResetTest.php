<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_routes_are_unavailable(): void
    {
        Notification::fake();
        User::factory()->create();

        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', [])->assertNotFound();
        $this->get('/reset-password/test-token')->assertNotFound();
        $this->post('/reset-password', [
            'token' => 'test-token',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        Notification::assertNothingSent();
    }
}
