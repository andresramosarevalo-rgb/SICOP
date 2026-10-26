<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Curso;
use App\Models\Empleado;
use App\Models\GastoCaja;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\TarifaGestor;
use App\Models\User;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    Auditoria::query()->delete();
});

// Issue #81, criterio 1
test('al crear un registro queda constancia de sus valores, de quién lo hizo y de la sede', function () {
    $sede = Sede::factory()->create();
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $curso = Curso::factory()->for($sede)->create(['nombre_cliente' => 'Carlos Pérez', 'fecha' => '2026-10-23', 'valor_curso' => 350000]);

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria)
        ->evento->toBe('creado')
        ->modulo->toBe('pos')
        ->etiqueta->toBe('Curso')
        ->descripcion->toBe('Carlos Pérez · 2026-10-23')
        ->user_id->toBe($this->gerente->id)
        ->sede_id->toBe($sede->id)
        ->auditable_type->toBe(Curso::class)
        ->auditable_id->toBe($curso->id)
        ->and($auditoria->cambios['nombre_cliente'])->toBe(['antes' => null, 'despues' => 'Carlos Pérez'])
        ->and($auditoria->cambios)->not->toHaveKeys(['id', 'created_at', 'updated_at']);
});

// Issue #81, criterio 1
test('al modificar un registro se guarda cada campo con su valor anterior y el nuevo', function () {
    $curso = Curso::factory()->create(['valor_curso' => 350000, 'porcentaje_descuento' => 50]);
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $curso->update(['valor_curso' => 300000, 'porcentaje_descuento' => 25]);

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria->evento)->toBe('modificado')
        ->and($auditoria->cambios)->toHaveCount(2)
        ->and(array_keys($auditoria->cambios))->toEqualCanonicalizing(['valor_curso', 'porcentaje_descuento'])
        ->and((float) $auditoria->cambios['valor_curso']['antes'])->toBe(350000.0)
        ->and((float) $auditoria->cambios['valor_curso']['despues'])->toBe(300000.0)
        ->and((float) $auditoria->cambios['porcentaje_descuento']['antes'])->toBe(50.0)
        ->and((float) $auditoria->cambios['porcentaje_descuento']['despues'])->toBe(25.0);
});

// Issue #81, criterio 2
test('guardar sin cambiar nada no genera registro', function () {
    $curso = Curso::factory()->create();
    Auditoria::query()->delete();

    $curso->update(['valor_curso' => $curso->valor_curso]);
    $curso->touch();

    expect(Auditoria::count())->toBe(0);
});

// Issue #81, criterio 1
test('al eliminar un registro se conservan sus valores y su descripción', function () {
    $cierre = CierreCaja::factory()->create();
    $gasto = GastoCaja::factory()->for($cierre)->create(['concepto' => 'Papelería']);
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $gasto->delete();

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria)
        ->evento->toBe('eliminado')
        ->etiqueta->toBe('Gasto de caja')
        ->descripcion->toBe('Papelería')
        ->sede_id->toBe($cierre->sede_id)
        ->and($auditoria->cambios['concepto'])->toBe(['antes' => 'Papelería', 'despues' => null]);
});

// Issue #81, criterio 1
test('sin sesión el cambio queda a nombre del sistema', function () {
    Sede::factory()->create();

    expect(Auditoria::firstOrFail()->user_id)->toBeNull();
});

// Issue #81, criterio 1
test('el cierre y la reapertura de caja quedan con su cambio de estado', function () {
    $cierre = CierreCaja::factory()->create();
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $cierre->update(['estado' => 'cerrado', 'cerrado_por' => $this->gerente->id]);
    $cierre->update(['estado' => 'abierto', 'cerrado_por' => null]);

    $auditorias = Auditoria::orderBy('id')->get();

    expect($auditorias)->toHaveCount(2)
        ->and($auditorias[0]->cambios['estado'])->toBe(['antes' => 'abierto', 'despues' => 'cerrado'])
        ->and($auditorias[1]->cambios['estado'])->toBe(['antes' => 'cerrado', 'despues' => 'abierto'])
        ->and($auditorias[1]->etiqueta)->toBe('Cierre de caja');
});

// Issue #81, criterio 1
test('la consignación y la tarifa del gestor se auditan con su sede', function () {
    $consignacion = Consignacion::factory()->create();
    $tarifa = TarifaGestor::factory()->paraSede(Sede::factory()->create(['nombre' => 'Sede Bogotá']))->create();

    expect(Auditoria::where('auditable_type', Consignacion::class)->firstOrFail()->sede_id)->toBe($consignacion->cierreCaja->sede_id)
        ->and(Auditoria::where('auditable_type', TarifaGestor::class)->firstOrFail())
        ->descripcion->toBe('Tipo C · Sede Bogotá')
        ->sede_id->toBe($tarifa->sede_id);
});

// Issue #81, criterio 1
test('un cambio en la tarifa del gestor deja el valor anterior y el nuevo', function () {
    $tarifa = TarifaGestor::factory()->create(['valor_pago_gestor' => 20000]);
    Auditoria::query()->delete();

    $this->actingAs($this->gerente);
    $tarifa->update(['valor_pago_gestor' => 25000]);

    $cambio = Auditoria::firstOrFail()->cambios['valor_pago_gestor'];

    expect((float) $cambio['antes'])->toBe(20000.0)->and((float) $cambio['despues'])->toBe(25000.0);
});

// Issue #81, criterios 2 y 3
test('la contraseña nunca se guarda y el token de sesión no genera registro', function () {
    $sede = Sede::factory()->create();
    $usuario = User::factory()->paraSede($sede)->create();
    Auditoria::query()->delete();

    $usuario->update(['remember_token' => 'otro-token']);
    expect(Auditoria::count())->toBe(0);

    $this->actingAs($this->gerente);
    $usuario->update(['password' => 'Clave-nueva-123']);

    $auditoria = Auditoria::firstOrFail();

    expect($auditoria->cambios['password'])->toBe(['antes' => '[oculto]', 'despues' => '[modificado]'])
        ->and($auditoria->modulo)->toBe('pos')
        ->and($auditoria->sede_id)->toBe($sede->id)
        ->and(json_encode($auditoria->cambios))->not->toContain('Clave-nueva-123');
});

// Issue #81, criterio 3
test('al crear un usuario no se guarda su contraseña', function () {
    $usuario = User::factory()->create(['password' => 'Clave-secreta-123']);

    $auditoria = Auditoria::where('auditable_type', User::class)->firstOrFail();

    expect($auditoria->modulo)->toBe('admin')
        ->and($auditoria->cambios['password']['despues'])->toBe('[oculto]')
        ->and(json_encode($auditoria->cambios))->not->toContain('Clave-secreta-123')
        ->and($usuario->exists)->toBeTrue();
});

// Issue #81, criterio 1
test('los cambios de nómina se auditan sin sede', function () {
    $this->actingAs($this->gerente);
    $area = Area::factory()->create(['nombre' => 'Instrucción']);
    $empleado = Empleado::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Ríos']);
    $empleado->update(['cargo' => 'Instructora']);

    expect(Auditoria::where('auditable_type', Area::class)->firstOrFail())
        ->modulo->toBe('nomina')
        ->descripcion->toBe('Instrucción')
        ->sede_id->toBeNull()
        ->and(Auditoria::where('auditable_type', Empleado::class)->where('evento', 'modificado')->firstOrFail())
        ->descripcion->toBe('Ana Ríos')
        ->and(Auditoria::where('auditable_type', Empleado::class)->where('evento', 'modificado')->first()->cambios['cargo']['despues'])->toBe('Instructora');
});

// Issue #81, criterio 1
test('los recibos de nómina no se auditan para no tapar el historial al liquidar', function () {
    ReciboNomina::factory()->create();
    Auditoria::query()->delete();

    ReciboNomina::query()->first()->update(['enviado_en' => now()]);

    expect(Auditoria::where('auditable_type', ReciboNomina::class)->count())->toBe(0);
});

// Issue #81, criterio 1
test('el registro de auditoría no se puede modificar', function () {
    $auditoria = Auditoria::factory()->create();

    expect(Auditoria::UPDATED_AT)->toBeNull()
        ->and($auditoria->created_at)->not->toBeNull();
});
