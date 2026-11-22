<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('el rol contador ya no existe ni se ofrece al registrar usuarios', function () {
    expect(Rol::tryFrom('contador'))->toBeNull();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.usuarios.create'))
        ->assertInertia(fn ($page) => $page->where('roles', fn ($roles) => ! collect($roles)->contains('valor', 'contador')));
});

test('la migración deshabilita las cuentas con rol contador y las pasa a cajero', function () {
    $usuario = User::factory()->create();
    DB::table('users')->where('id', $usuario->id)->update(['rol' => 'contador', 'es_activo' => true]);

    (require database_path('migrations/2026_11_22_210000_retirar_rol_contador.php'))->up();

    $fila = DB::table('users')->find($usuario->id);

    expect($fila->rol)->toBe('cajero')->and((bool) $fila->es_activo)->toBeFalse();
});
