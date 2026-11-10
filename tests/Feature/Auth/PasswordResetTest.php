<?php

use App\Models\User;

// Issue #171
test('forgot password flow is not available', function () {
    $user = User::factory()->create();

    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
    $this->get('/reset-password/token')->assertNotFound();
    $this->post('/reset-password', [
        'token' => 'token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertNotFound();
});

// Issue #171
test('login page does not offer registration or password recovery', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->missing('canResetPassword'),
        );
});
