<?php

use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\GastoCaja;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create(['valor_base_caja' => 200000]);
});

function datosGasto(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        'concepto' => 'Papelería',
        'valor_gasto' => '15000',
        'soporte' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
        ...$sobrescribir,
    ];
}

// Issue #76, criterio 1
test('el resumen suma las ventas del día por medio de pago y el pago a gestores', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 300000, 'forma_pago' => 'efectivo', 'valor_efectivo' => 300000, 'valor_pago_gestor' => 20000]);
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 250000, 'forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 100000, 'valor_qr' => 50000, 'valor_pago_gestor' => 10000]);
    Soat::factory()->for($this->sede)->create(['fecha' => today(), 'valor_soat' => 580000, 'forma_pago' => 'mixto', 'valor_efectivo' => 280000, 'valor_qr' => 300000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cierre/Show')
            ->where('fecha', today()->toDateString())
            ->where('resumen.valor_base_caja', '200000.00')
            ->where('resumen.valor_efectivo_ventas', '680000.00')
            ->where('resumen.valor_ventas_datafono', '100000.00')
            ->where('resumen.valor_ventas_qr', '350000.00')
            ->where('resumen.valor_pago_gestores', '30000.00')
            ->where('resumen.cantidad_cursos', 2)
            ->where('resumen.cantidad_soats', 1)
            ->where('valorEfectivoEsperado', 850000)
            ->where('estaCerrado', false));
});

// Issue #76, criterio 1
test('el resumen solo cuenta las ventas de esa sede y de ese día', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_efectivo' => 100000]);
    Curso::factory()->for($this->sede)->create(['fecha' => today()->subDay(), 'valor_efectivo' => 999000]);
    Curso::factory()->create(['fecha' => today(), 'valor_efectivo' => 888000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->where('resumen.valor_efectivo_ventas', '100000.00'));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->subDay()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('resumen.valor_efectivo_ventas', '999000.00'));
});

// Issue #76, criterio 3
test('los gastos restan del efectivo que debe haber en caja', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_efectivo' => 100000, 'valor_pago_gestor' => 0]);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['valor_gasto' => '15000']))
        ->assertRedirect(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->toDateString()]));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->has('gastos', 1)
            ->where('gastos.0.concepto', 'Papelería')
            ->where('valorGastos', 15000)
            ->where('valorEfectivoEsperado', 285000));
});

// Issue #76, criterio 3
test('el gasto guarda su soporte en el disco privado', function () {
    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto());

    $gasto = GastoCaja::firstOrFail();

    Storage::disk('local')->assertExists($gasto->ruta_soporte);
    expect($gasto->nombre_soporte)->toBe('factura.pdf')
        ->and(CierreCaja::count())->toBe(1);
});

// Issue #76, criterio 3
test('el gasto exige el soporte, un formato permitido y un tamaño máximo', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['soporte' => null]))
        ->assertSessionHasErrors(['soporte' => 'Adjunte el soporte del gasto.']);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['soporte' => UploadedFile::fake()->create('virus.exe', 10)]))
        ->assertSessionHasErrors(['soporte' => 'El soporte debe ser una imagen JPG o PNG, o un PDF.']);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['soporte' => UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')]))
        ->assertSessionHasErrors(['soporte' => 'El soporte no puede pesar más de 5 MB.']);

    expect(GastoCaja::count())->toBe(0);
});

// Issue #76, criterio 3
test('el gasto exige concepto, valor mayor que cero y fecha no futura', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['concepto' => '', 'valor_gasto' => 0, 'fecha' => today()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['concepto', 'valor_gasto', 'fecha']);
});

// Issue #76, criterio 4
test('al eliminar un gasto se borra también su soporte', function () {
    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto());
    $gasto = GastoCaja::firstOrFail();

    $this->actingAs($this->gerente)
        ->delete(route('pos.cierre.gastos.destroy', $gasto))
        ->assertRedirect();

    expect(GastoCaja::count())->toBe(0);
    Storage::disk('local')->assertMissing($gasto->ruta_soporte);
});

// Issue #77, criterio 3
test('un día cerrado no admite agregar ni eliminar gastos', function () {
    $cierre = CierreCaja::factory()->cerrado()->for($this->sede)->create();
    $gasto = GastoCaja::factory()->for($cierre)->create();

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto())
        ->assertForbidden();
    $this->actingAs($this->gerente)->delete(route('pos.cierre.gastos.destroy', $gasto))->assertForbidden();

    expect(GastoCaja::count())->toBe(1);
});

// Issue #76, criterio 3
test('una sede inactiva no registra gastos', function () {
    $sede = Sede::factory()->create(['es_activa' => false]);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.gastos.store', $sede), datosGasto())
        ->assertForbidden();
});

// Issue #76, criterio 1
test('la fecha de la consulta no puede ser futura ni inválida', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['fecha' => 'La fecha no puede ser futura.']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => 'ayer']))
        ->assertSessionHasErrors(['fecha' => 'Escriba una fecha válida.']);
});

// Issue #76, criterio 5
test('el usuario de la sede ve y gestiona solo la caja de su sede', function () {
    $usuarioSede = User::factory()->paraSede($this->sede)->create();
    $otraSede = Sede::factory()->create();

    $this->actingAs($usuarioSede)->get(route('pos.sedes.cierre.show', $this->sede))->assertOk();
    $this->actingAs($usuarioSede)
        ->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto())
        ->assertRedirect();

    $this->actingAs($usuarioSede)->get(route('pos.sedes.cierre.show', $otraSede))->assertForbidden();
    $this->actingAs($usuarioSede)
        ->post(route('pos.sedes.cierre.gastos.store', $otraSede), datosGasto())
        ->assertForbidden();
});

// Issue #76, criterio 4
test('el soporte solo lo descarga quien opera la sede', function () {
    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto());
    $gasto = GastoCaja::firstOrFail();

    $this->actingAs($this->gerente)->get(route('pos.cierre.gastos.soporte', $gasto))->assertOk();
    $this->actingAs(User::factory()->paraSede($this->sede)->create())->get(route('pos.cierre.gastos.soporte', $gasto))->assertOk();
    $this->actingAs(User::factory()->paraSede(Sede::factory()->create())->create())->get(route('pos.cierre.gastos.soporte', $gasto))->assertForbidden();
    $this->actingAs(User::factory()->conRol(Rol::Contador)->create())->get(route('pos.cierre.gastos.soporte', $gasto))->assertForbidden();
});

// Issue #76, criterio 2
test('el gerente fija la base de caja de la sede', function () {
    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.update', $this->sede), ['nombre' => $this->sede->nombre, 'ciudad' => 'Villavicencio', 'direccion' => 'Calle 1', 'valor_base_caja' => '350000'])
        ->assertRedirect();

    expect($this->sede->fresh()->valor_base_caja)->toBe('350000.00');

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.update', $this->sede), ['nombre' => $this->sede->nombre, 'ciudad' => 'Villavicencio', 'direccion' => 'Calle 1', 'valor_base_caja' => '-1'])
        ->assertSessionHasErrors('valor_base_caja');
});

// Issue #134, criterio 1
test('si falla el borrado o el registro de un gasto no se pierde ni sobra un soporte', function () {
    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto());
    $gasto = GastoCaja::firstOrFail();

    GastoCaja::deleting(fn () => throw new RuntimeException('falla al borrar'));
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($this->gerente)->delete(route('pos.cierre.gastos.destroy', $gasto)))->toThrow(RuntimeException::class);
    Storage::disk('local')->assertExists($gasto->ruta_soporte);

    GastoCaja::creating(fn () => throw new RuntimeException('falla al crear'));

    expect(fn () => $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.gastos.store', $this->sede), datosGasto(['concepto' => 'Otro'])))->toThrow(RuntimeException::class)
        ->and(Storage::disk('local')->allFiles('soportes-caja'))->toHaveCount(1);
});
