<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('la pagina inicial redirige al login', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));

    $this->followingRedirects()->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
});

test('la pagina inicial conserva el acceso de usuarios autenticados', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->followingRedirects()->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});
