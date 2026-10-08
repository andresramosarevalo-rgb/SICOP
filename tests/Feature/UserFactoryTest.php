<?php

use App\Models\User;

// Issue #18, criterio 2 (el criterio 1, PHPStan sin errores, lo verifica el CI con composer types:check)
test('el estado withTwoFactor deja configurada la autenticación de dos factores', function () {
    $usuario = User::factory()->withTwoFactor()->make();

    expect($usuario->two_factor_secret)->not->toBeNull()
        ->and($usuario->two_factor_recovery_codes)->not->toBeNull()
        ->and($usuario->two_factor_confirmed_at)->not->toBeNull();
});
