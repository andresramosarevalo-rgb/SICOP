<?php

use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
    $this->usuarioSede = User::factory()->paraSede($this->sede)->create();
    $this->cierre = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDay(), 'valor_a_consignar' => 300000]);
});

function datosConsignacion(array $sobrescribir = []): array
{
    return [
        'fecha_consignacion' => today()->toDateString(),
        'valor_consignado' => '300000',
        'referencia' => 'REF-001',
        'soporte' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ...$sobrescribir,
    ];
}

// Issue #79, criterio 1
test('el gerente registra la consignación de un día cerrado con su comprobante', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion())
        ->assertRedirect(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->subDay()->toDateString()]))
        ->assertSessionHasNoErrors();

    $consignacion = Consignacion::firstOrFail();

    expect($consignacion)
        ->cierre_caja_id->toBe($this->cierre->id)
        ->valor_consignado->toBe('300000.00')
        ->referencia->toBe('REF-001')
        ->nombre_soporte->toBe('comprobante.pdf')
        ->registrado_por->toBe($this->gerente->id);
    Storage::disk('local')->assertExists($consignacion->ruta_soporte);
});

// Issue #79, criterio 2
test('solo se puede consignar una vez por día', function () {
    $this->actingAs($this->gerente)->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion());

    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion())
        ->assertForbidden();

    expect(Consignacion::count())->toBe(1);
});

// Issue #79, criterio 4
test('no se consigna un día que no está cerrado', function () {
    $abierto = CierreCaja::factory()->for($this->sede)->create(['fecha' => today()]);

    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $abierto), datosConsignacion())
        ->assertForbidden();
});

// Issue #79, criterio 3
test('la consignación valida fecha, valor, referencia y comprobante', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), [])
        ->assertSessionHasErrors(['fecha_consignacion', 'valor_consignado', 'referencia', 'soporte']);

    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion(['fecha_consignacion' => today()->subDays(3)->toDateString()]))
        ->assertSessionHasErrors(['fecha_consignacion' => 'La consignación no puede ser anterior al día cerrado.']);

    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion(['fecha_consignacion' => today()->addDay()->toDateString(), 'valor_consignado' => 0, 'soporte' => UploadedFile::fake()->create('virus.exe', 10)]))
        ->assertSessionHasErrors(['fecha_consignacion', 'valor_consignado', 'soporte']);

    expect(Consignacion::count())->toBe(0);
});

// Issue #79, criterio 3
test('no se puede consignar más de lo que la sede debía consignar', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion(['valor_consignado' => '300000.01']))
        ->assertSessionHasErrors(['valor_consignado' => 'No puede superar lo que debía consignar la sede.']);
});

// Issue #79, criterio 1
test('el día cerrado muestra su consignación y lo pendiente', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->subDay()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('cierreId', $this->cierre->id)->where('consignacion', null));

    Consignacion::factory()->create(['cierre_caja_id' => $this->cierre->id, 'referencia' => 'REF-ABC']);

    $this->actingAs($this->usuarioSede)
        ->get(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->subDay()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('consignacion.referencia', 'REF-ABC'));
});

// Issue #79, criterio 5
test('el gerente elimina una consignación y se borra su comprobante', function () {
    $this->actingAs($this->gerente)->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion());
    $consignacion = Consignacion::firstOrFail();

    $this->actingAs($this->gerente)
        ->delete(route('pos.consignaciones.destroy', $consignacion))
        ->assertRedirect();

    expect(Consignacion::count())->toBe(0);
    Storage::disk('local')->assertMissing($consignacion->ruta_soporte);
});

// Issue #79, criterio 5
test('un día con consignación no se puede reabrir hasta eliminarla', function () {
    Consignacion::factory()->create(['cierre_caja_id' => $this->cierre->id]);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->subDay()->toDateString()])
        ->assertForbidden();

    expect($this->cierre->fresh()->estaCerrado())->toBeTrue();
});

// Issue #79, criterio 4
test('el usuario de la sede no registra ni elimina consignaciones pero sí ve su comprobante', function () {
    $consignacion = Consignacion::factory()->create(['cierre_caja_id' => $this->cierre->id]);
    Storage::disk('local')->put($consignacion->ruta_soporte, 'contenido');

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion())
        ->assertForbidden();
    $this->actingAs($this->usuarioSede)->delete(route('pos.consignaciones.destroy', $consignacion))->assertForbidden();

    $this->actingAs($this->usuarioSede)->get(route('pos.consignaciones.soporte', $consignacion))->assertOk();
    $this->actingAs(User::factory()->paraSede(Sede::factory()->create())->create())
        ->get(route('pos.consignaciones.soporte', $consignacion))
        ->assertForbidden();
});

test('el usuario de la sede no registra consignaciones pero sí ve su comprobante', function () {
    $consignacion = Consignacion::factory()->create(['cierre_caja_id' => $this->cierre->id]);
    Storage::disk('local')->put($consignacion->ruta_soporte, 'contenido');

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion())
        ->assertForbidden();

    $this->actingAs($this->usuarioSede)->get(route('pos.consignaciones.soporte', $consignacion))->assertOk();
    $this->actingAs(User::factory()->paraSede(Sede::factory()->create())->create())
        ->get(route('pos.consignaciones.soporte', $consignacion))
        ->assertForbidden();
});

// Issue #134, criterio 1
test('si falla el borrado o el registro de una consignación no se pierde ni sobra un comprobante', function () {
    $this->actingAs($this->gerente)->post(route('pos.cierre.consignacion.store', $this->cierre), datosConsignacion());
    $consignacion = Consignacion::firstOrFail();

    Consignacion::deleting(fn () => throw new RuntimeException('falla al borrar'));
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($this->gerente)->delete(route('pos.consignaciones.destroy', $consignacion)))->toThrow(RuntimeException::class);
    Storage::disk('local')->assertExists($consignacion->ruta_soporte);

    $otro = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(2), 'valor_a_consignar' => 100000]);
    Consignacion::creating(fn () => throw new RuntimeException('falla al crear'));

    expect(fn () => $this->actingAs($this->gerente)->post(route('pos.cierre.consignacion.store', $otro), datosConsignacion(['valor_consignado' => '50000'])))->toThrow(RuntimeException::class)
        ->and(Storage::disk('local')->allFiles('soportes-consignacion'))->toHaveCount(1);
});
