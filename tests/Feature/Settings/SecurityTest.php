<?php

use App\Models\User;

// Issue #171
test('users cannot access or change their password from settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings/security')->assertNotFound();

    $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertNotFound();
});
