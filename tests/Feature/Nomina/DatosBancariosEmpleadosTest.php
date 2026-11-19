<?php

use App\Enums\Rol;
use App\Enums\TipoCuentaBancaria;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sede = Sede::factory()->create();
    $this->auxiliar = User::factory()->enSede($this->sede, Rol::AuxiliarNomina)->create();
});

/**
 * Una edición que conserva los datos del empleado, incluidos los bancarios.
 *
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function datosEdicionEmpleadoConBanco(Empleado $empleado, array $cambios = []): array
{
    return [
        ...$empleado->only(['numero_documento', 'nombres', 'apellidos', 'email', 'telefono', 'direccion', 'area_id', 'sede_id', 'cargo']),
        'banco' => $empleado->banco,
        'numero_cuenta_bancaria' => $empleado->numero_cuenta_bancaria,
        'tipo_cuenta_bancaria' => $empleado->tipo_cuenta_bancaria?->value,
        'tipo_documento' => $empleado->tipo_documento->value,
        'fecha_nacimiento' => $empleado->fecha_nacimiento?->toDateString(),
        ...$cambios,
    ];
}

dataset('tipos de cuenta bancaria', [
    'ahorros' => ['ahorros', 'Ahorros'],
    'corriente' => ['corriente', 'Corriente'],
    'pensional' => ['pensional', 'Pensional'],
    'nómina' => ['nomina', 'Nómina'],
]);

// Issue #187, criterio 1 y ampliación: los formularios ofrecen los cuatro tipos acordados.
test('registro y edición ofrecen exactamente los cuatro tipos de cuenta', function () {
    $empleado = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    $opciones = [
        ['valor' => 'ahorros', 'etiqueta' => 'Ahorros'],
        ['valor' => 'corriente', 'etiqueta' => 'Corriente'],
        ['valor' => 'pensional', 'etiqueta' => 'Pensional'],
        ['valor' => 'nomina', 'etiqueta' => 'Nómina'],
    ];

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Create')
            ->where('tiposCuentaBancaria', $opciones));

    $this->get(route('nomina.empleados.edit', $empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Edit')
            ->where('tiposCuentaBancaria', $opciones)
            ->where('empleado.banco', null)
            ->where('empleado.numero_cuenta_bancaria', null)
            ->where('empleado.tipo_cuenta_bancaria', null));
});

// Issue #187, criterios 1 y 2: persistencia, ceros iniciales y los cuatro tipos.
test('registra y consulta la información bancaria con su contrato', function (string $tipo, string $etiqueta) {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.store'), datosEmpleado([
            'area_id' => Area::factory()->create()->id,
            'tipo_cuenta_bancaria' => $tipo,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.empleados.show', Empleado::sole()));

    $empleado = Empleado::sole();
    $this->assertDatabaseHas('empleados', [
        'id' => $empleado->id,
        'sede_id' => $this->sede->id,
        'banco' => 'Banco de prueba',
        'numero_cuenta_bancaria' => '001234567890',
        'tipo_cuenta_bancaria' => $tipo,
    ]);
    expect($empleado->contratoVigente)->not->toBeNull();

    foreach (['show', 'edit'] as $accion) {
        $this->get(route('nomina.empleados.'.$accion, $empleado))
            ->assertInertia(fn (Assert $page) => $page
                ->where('empleado.banco', 'Banco de prueba')
                ->where('empleado.numero_cuenta_bancaria', '001234567890')
                ->where('empleado.tipo_cuenta_bancaria', $tipo));
    }

    $this->get(route('nomina.empleados.show', $empleado))
        ->assertInertia(fn (Assert $page) => $page->where('tipoCuentaBancaria', $etiqueta));

    $auditoria = Auditoria::where('auditable_type', Empleado::class)->where('evento', 'creado')->sole();
    expect($auditoria->user_id)->toBe($this->auxiliar->id)
        ->and($auditoria->sede_id)->toBe($this->sede->id)
        ->and($auditoria->cambios['numero_cuenta_bancaria'])->toBe(['antes' => null, 'despues' => '001234567890'])
        ->and($auditoria->cambios['tipo_cuenta_bancaria']['despues'])->toBe($tipo);
})->with('tipos de cuenta bancaria');

// Issue #187, criterio 4: no guarda empleado ni contrato con información bancaria incompleta.
test('cada campo bancario es obligatorio al registrar', function (string $campo, string $mensaje, mixed $valor) {
    $datos = datosEmpleado(['area_id' => Area::factory()->create()->id, $campo => $valor]);
    if ($valor === 'omitido') {
        unset($datos[$campo]);
    }

    $this->actingAs($this->auxiliar)->post(route('nomina.empleados.store'), $datos)
        ->assertSessionHasErrors([$campo => $mensaje]);

    expect(Empleado::count())->toBe(0)->and(Contrato::count())->toBe(0);
})->with([
    ['banco', 'Ingrese el banco.'],
    ['numero_cuenta_bancaria', 'Ingrese el número de cuenta bancaria.'],
    ['tipo_cuenta_bancaria', 'Seleccione el tipo de cuenta bancaria.'],
])->with(['omitido', null, '', '   ']);

// Issue #187, criterio 4 y ampliación: validación también al editar.
test('rechaza tipos y longitudes bancarias inválidas sin cambiar datos', function (string $campo, mixed $valor, string $mensaje, bool $editar) {
    $empleado = Empleado::factory()->create([
        'sede_id' => $this->sede->id, 'banco' => 'Banco original',
        'numero_cuenta_bancaria' => '000123', 'tipo_cuenta_bancaria' => 'ahorros',
    ]);
    $antes = $empleado->refresh()->getRawOriginal();
    $this->actingAs($this->auxiliar);

    $respuesta = $editar
        ? $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, [$campo => $valor]))
        : $this->post(route('nomina.empleados.store'), datosEmpleado(['area_id' => $empleado->area_id, $campo => $valor]));

    $respuesta->assertSessionHasErrors([$campo => $mensaje]);
    expect($empleado->fresh()->getRawOriginal())->toBe($antes)
        ->and(Empleado::count())->toBe(1)
        ->and(Contrato::count())->toBe(0);
})->with([
    ['banco', ['otro'], 'El banco debe ser un texto.'],
    ['banco', 123, 'El banco debe ser un texto.'],
    ['banco', str_repeat('a', 256), 'El banco no debe superar los 255 caracteres.'],
    ['numero_cuenta_bancaria', ['123'], 'El número de cuenta bancaria debe ser un texto.'],
    ['numero_cuenta_bancaria', 123, 'El número de cuenta bancaria debe ser un texto.'],
    ['numero_cuenta_bancaria', str_repeat('0', 256), 'El número de cuenta bancaria no debe superar los 255 caracteres.'],
    ['tipo_cuenta_bancaria', 'inexistente', 'Seleccione un tipo de cuenta bancaria válido.'],
    ['tipo_cuenta_bancaria', ['ahorros'], 'Seleccione un tipo de cuenta bancaria válido.'],
])->with([false, true]);

// Issue #187, criterios 1 y 3: límite de almacenamiento de los dos campos de texto.
test('acepta 255 caracteres en banco y cuenta al registrar y editar', function () {
    $banco = str_repeat('á', 255);
    $cuenta = str_repeat('0', 255);

    $this->actingAs($this->auxiliar)->post(route('nomina.empleados.store'), datosEmpleado([
        'area_id' => Area::factory()->create()->id, 'banco' => $banco, 'numero_cuenta_bancaria' => $cuenta,
    ]))->assertSessionHasNoErrors();

    $empleado = Empleado::sole();
    expect($empleado->banco)->toBe($banco)->and($empleado->numero_cuenta_bancaria)->toBe($cuenta);

    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, [
        'banco' => str_repeat('b', 255), 'numero_cuenta_bancaria' => str_repeat('1', 255),
    ]))->assertSessionHasNoErrors();

    expect($empleado->fresh()->banco)->toBe(str_repeat('b', 255))
        ->and($empleado->fresh()->numero_cuenta_bancaria)->toBe(str_repeat('1', 255));
});

// Issue #187, criterio 3 y ampliación: permite cambiar a cualquiera de los cuatro tipos.
test('edita los datos bancarios y conserva el resto del expediente', function (string $tipo, string $etiqueta) {
    $empleado = Empleado::factory()->create([
        'sede_id' => $this->sede->id, 'banco' => 'Banco anterior',
        'numero_cuenta_bancaria' => '000123', 'tipo_cuenta_bancaria' => $tipo === 'ahorros' ? 'corriente' : 'ahorros',
    ]);
    $contrato = Contrato::factory()->for($empleado)->create();
    $antes = Arr::except($empleado->refresh()->getRawOriginal(), [...Empleado::CAMPOS_BANCARIOS, 'updated_at']);
    $contratoAntes = $contrato->refresh()->getRawOriginal();

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, [
            'banco' => 'Banco nuevo', 'numero_cuenta_bancaria' => '000987', 'tipo_cuenta_bancaria' => $tipo,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.empleados.show', $empleado));

    $this->get(route('nomina.empleados.show', $empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('empleado.banco', 'Banco nuevo')
            ->where('empleado.numero_cuenta_bancaria', '000987')
            ->where('empleado.tipo_cuenta_bancaria', $tipo)
            ->where('tipoCuentaBancaria', $etiqueta));

    expect(Arr::except($empleado->fresh()->getRawOriginal(), [...Empleado::CAMPOS_BANCARIOS, 'updated_at']))->toBe($antes)
        ->and($contrato->fresh()->getRawOriginal())->toBe($contratoAntes);

    $auditoria = Auditoria::where('auditable_type', Empleado::class)->where('evento', 'modificado')->sole();
    expect($auditoria->cambios['banco'])->toBe(['antes' => 'Banco anterior', 'despues' => 'Banco nuevo'])
        ->and($auditoria->cambios['numero_cuenta_bancaria'])->toBe(['antes' => '000123', 'despues' => '000987'])
        ->and($auditoria->cambios['tipo_cuenta_bancaria']['despues'])->toBe($tipo);
})->with('tipos de cuenta bancaria');

// Issue #187: los tres datos bancarios también son obligatorios al guardar una edición.
test('editar rechaza cada campo bancario vacío u omitido sin modificar empleado ni auditoría', function (string $campo, string $mensaje, mixed $valor) {
    $empleado = Empleado::factory()->create([
        'sede_id' => $this->sede->id, 'banco' => 'Banco original',
        'numero_cuenta_bancaria' => '00123', 'tipo_cuenta_bancaria' => 'ahorros',
    ]);
    $antes = $empleado->refresh()->getRawOriginal();
    $auditorias = Auditoria::count();
    $datos = datosEdicionEmpleadoConBanco($empleado, ['cargo' => 'Supervisora', $campo => $valor]);
    if ($valor === 'omitido') {
        unset($datos[$campo]);
    }

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.empleados.update', $empleado), $datos)
        ->assertSessionHasErrors([$campo => $mensaje]);

    expect($empleado->fresh()->getRawOriginal())->toBe($antes)
        ->and(Auditoria::count())->toBe($auditorias);
})->with([
    ['banco', 'Ingrese el banco.'],
    ['numero_cuenta_bancaria', 'Ingrese el número de cuenta bancaria.'],
    ['tipo_cuenta_bancaria', 'Seleccione el tipo de cuenta bancaria.'],
])->with(['omitido', null, '', '   ']);

// Issue #187, criterio 5: actualización real de un esquema anterior, sin alterar los registros.
test('la migración conserva empleados y contratos históricos y permite completar sus datos', function () {
    $migracion = require database_path('migrations/2026_11_16_000001_agregar_datos_bancarios_a_empleados_table.php');
    $migracion->down();
    $empleado = Empleado::factory()->create(['sede_id' => $this->sede->id, 'es_activo' => false]);
    $contrato = Contrato::factory()->for($empleado)->create();
    $antes = (array) DB::table('empleados')->find($empleado->id);
    $contratoAntes = $contrato->refresh()->getRawOriginal();

    $migracion->up();

    $despues = (array) DB::table('empleados')->find($empleado->id);
    expect(Arr::except($despues, Empleado::CAMPOS_BANCARIOS))->toBe($antes)
        ->and(Arr::only($despues, Empleado::CAMPOS_BANCARIOS))->toBe(array_fill_keys(Empleado::CAMPOS_BANCARIOS, null))
        ->and(Empleado::count())->toBe(1)
        ->and($contrato->fresh()->getRawOriginal())->toBe($contratoAntes);

    $this->actingAs($this->auxiliar)->get(route('nomina.empleados.show', $empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('empleado.banco', null)
            ->where('empleado.numero_cuenta_bancaria', null)
            ->where('empleado.tipo_cuenta_bancaria', null)
            ->where('tipoCuentaBancaria', null));

    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, ['cargo' => 'Coordinadora']))
        ->assertSessionHasErrors(Empleado::CAMPOS_BANCARIOS);
    expect($empleado->fresh()->banco)->toBeNull();

    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado->fresh(), [
        'banco' => 'Banco de prueba', 'numero_cuenta_bancaria' => '000456', 'tipo_cuenta_bancaria' => 'pensional',
    ]))->assertSessionHasNoErrors();

    expect($empleado->fresh()->numero_cuenta_bancaria)->toBe('000456')
        ->and($empleado->fresh()->tipo_cuenta_bancaria)->toBe(TipoCuentaBancaria::Pensional)
        ->and($empleado->fresh()->es_activo)->toBeFalse()
        ->and($contrato->fresh()->getRawOriginal())->toBe($contratoAntes);
});

// Issue #187, permisos y exposición: solo expediente y edición incluyen datos bancarios.
test('el listado omite información bancaria incluso para el super administrador', function (Rol $rol) {
    $usuario = User::factory()->enSede($this->sede, $rol)->create();
    $empleado = Empleado::factory()->create([
        'sede_id' => $this->sede->id, 'banco' => 'Banco privado',
        'numero_cuenta_bancaria' => '001234', 'tipo_cuenta_bancaria' => 'ahorros',
    ]);

    expect($empleado->toArray())->not->toHaveKeys(Empleado::CAMPOS_BANCARIOS);

    $this->actingAs($usuario)->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('empleados', 1)
            ->missing('empleados.0.banco')
            ->missing('empleados.0.numero_cuenta_bancaria')
            ->missing('empleados.0.tipo_cuenta_bancaria'));

    $this->get(route('nomina.empleados.show', $empleado))
        ->assertInertia(fn (Assert $page) => $page->where('empleado.numero_cuenta_bancaria', '001234'));
})->with([Rol::AuxiliarNomina, Rol::Administrador, Rol::SuperAdmin]);

// Issue #187, permisos: crear y editar no permite cambiar a otra sede.
test('administrador y auxiliar guardan datos bancarios solo en su propia sede', function (Rol $rol) {
    $usuario = User::factory()->enSede($this->sede, $rol)->create();
    $otraSede = Sede::factory()->create();

    $this->actingAs($usuario)->post(route('nomina.empleados.store'), datosEmpleado([
        'area_id' => Area::factory()->create()->id, 'sede_id' => $otraSede->id,
    ]))->assertSessionHasNoErrors();

    $empleado = Empleado::sole();
    expect($empleado->sede_id)->toBe($this->sede->id);

    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, [
        'sede_id' => $otraSede->id, 'numero_cuenta_bancaria' => '000999',
    ]))->assertSessionHasNoErrors();

    expect($empleado->fresh()->sede_id)->toBe($this->sede->id)
        ->and($empleado->fresh()->numero_cuenta_bancaria)->toBe('000999');
})->with([Rol::AuxiliarNomina, Rol::Administrador]);

// Issue #187, permisos: tampoco hay acceso por URL ni al enviar un PUT de otra sede.
test('administrador y auxiliar no consultan ni modifican datos bancarios ajenos o sin sede', function (Rol $rol, bool $sinSede) {
    $usuario = User::factory()->enSede($this->sede, $rol)->create();
    $empleado = Empleado::factory()->create([
        'sede_id' => $sinSede ? null : Sede::factory()->create()->id,
        'banco' => 'Banco privado', 'numero_cuenta_bancaria' => '001234', 'tipo_cuenta_bancaria' => 'corriente',
    ]);
    $antes = $empleado->refresh()->getRawOriginal();

    $this->actingAs($usuario)->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 0));
    $this->get(route('nomina.empleados.show', $empleado))->assertNotFound();
    $this->get(route('nomina.empleados.edit', $empleado))->assertNotFound();
    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, ['banco' => 'Otro']))
        ->assertNotFound();

    expect($empleado->fresh()->getRawOriginal())->toBe($antes);
})->with([Rol::AuxiliarNomina, Rol::Administrador])->with([false, true]);

// Issue #187, permisos: acceso global del Superadmin, respetando las reglas vigentes de sede.
test('el super administrador registra consulta y edita datos bancarios en cualquier sede', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $otraSede = Sede::factory()->create();
    $this->actingAs($superAdmin);

    foreach ([$this->sede, $otraSede] as $sede) {
        $this->post(route('nomina.empleados.store'), datosEmpleado([
            'numero_documento' => 'DOC'.$sede->id, 'sede_id' => $sede->id, 'area_id' => Area::factory()->create()->id,
        ]))->assertSessionHasNoErrors();

        $empleado = Empleado::where('sede_id', $sede->id)->sole();
        $this->get(route('nomina.empleados.show', $empleado))->assertOk();
        $this->get(route('nomina.empleados.edit', $empleado))->assertOk();
        $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, ['banco' => 'Banco actualizado']))
            ->assertSessionHasNoErrors();

        expect($empleado->fresh()->banco)->toBe('Banco actualizado')
            ->and($empleado->fresh()->sede_id)->toBe($sede->id);
    }

    $sinSede = Empleado::factory()->create(['sede_id' => null]);
    $this->get(route('nomina.empleados.show', $sinSede))->assertOk();
    $this->get(route('nomina.empleados.edit', $sinSede))->assertOk();
});

// Issue #187, permisos: no se amplía el acceso a nómina de otros roles.
test('los roles sin permiso no acceden a los datos bancarios ni los modifican', function (?Rol $rol) {
    $usuario = User::factory()->create(['rol' => $rol, 'sede_id' => $this->sede->id]);
    $empleado = Empleado::factory()->create(['sede_id' => $this->sede->id, 'banco' => 'Banco original']);
    $this->actingAs($usuario);
    foreach (['index', 'create', 'show', 'edit'] as $accion) {
        $this->get(route('nomina.empleados.'.$accion, in_array($accion, ['show', 'edit']) ? $empleado : []))
            ->assertForbidden();
    }
    $this->post(route('nomina.empleados.store'), datosEmpleado(['area_id' => $empleado->area_id]))->assertForbidden();
    $this->put(route('nomina.empleados.update', $empleado), datosEdicionEmpleadoConBanco($empleado, ['banco' => 'Otro']))
        ->assertForbidden();

    expect($empleado->fresh()->banco)->toBe('Banco original')->and(Empleado::count())->toBe(1);
})->with([Rol::Cajero, Rol::Contador, null]);

// Issue #187, permisos: un visitante no consulta ni registra información bancaria.
test('un visitante debe iniciar sesión para acceder a empleados', function () {
    $empleado = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    foreach (['index', 'create', 'show', 'edit'] as $accion) {
        $this->get(route('nomina.empleados.'.$accion, in_array($accion, ['show', 'edit']) ? $empleado : []))
            ->assertRedirect(route('login'));
    }
    $this->post(route('nomina.empleados.store'), [])->assertRedirect(route('login'));
    $this->put(route('nomina.empleados.update', $empleado), [])->assertRedirect(route('login'));
});
