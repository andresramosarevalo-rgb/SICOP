<?php

use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\Sede;
use App\Models\User;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    Auditoria::query()->delete();
});

// Issue #81, criterio 1
test('al crear un registro queda constancia de sus valores, de quién lo hizo y de la sede', function () {
    $this->actingAs($this->gerente);
    $sede = Sede::factory()->create(['nombre' => 'Sede Villavicencio', 'ciudad' => 'Villavicencio']);

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria)
        ->evento->toBe('creado')
        ->modulo->toBe('pos')
        ->etiqueta->toBe('Sede')
        ->descripcion->toBe('Sede Villavicencio')
        ->user_id->toBe($this->gerente->id)
        ->sede_id->toBe($sede->id)
        ->auditable_type->toBe(Sede::class)
        ->auditable_id->toBe($sede->id)
        ->and($auditoria->cambios['ciudad'])->toBe(['antes' => null, 'despues' => 'Villavicencio'])
        ->and($auditoria->cambios)->not->toHaveKeys(['id', 'created_at', 'updated_at']);
});

// Issue #81, criterio 1
test('al modificar un registro se guarda cada campo con su valor anterior y el nuevo', function () {
    $sede = Sede::factory()->create(['ciudad' => 'Villavicencio', 'direccion' => 'Calle 1']);
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $sede->update(['ciudad' => 'Acacías', 'direccion' => 'Calle 2']);

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria->evento)->toBe('modificado')
        ->and($auditoria->cambios)->toHaveCount(2)
        ->and($auditoria->cambios['ciudad'])->toBe(['antes' => 'Villavicencio', 'despues' => 'Acacías'])
        ->and($auditoria->cambios['direccion'])->toBe(['antes' => 'Calle 1', 'despues' => 'Calle 2']);
});

// Issue #81, criterio 2
test('guardar sin cambiar nada no genera registro', function () {
    $sede = Sede::factory()->create();
    Auditoria::query()->delete();

    $sede->update(['ciudad' => $sede->ciudad]);
    $sede->touch();

    expect(Auditoria::count())->toBe(0);
});

// Issue #81, criterio 1
test('sin sesión el cambio queda a nombre del sistema', function () {
    Sede::factory()->create();

    expect(Auditoria::firstOrFail()->user_id)->toBeNull();
});

// Issue #81, criterio 1
test('el registro de auditoría no se puede modificar', function () {
    $auditoria = Auditoria::factory()->create();

    expect(Auditoria::UPDATED_AT)->toBeNull()
        ->and($auditoria->created_at)->not->toBeNull();
});
