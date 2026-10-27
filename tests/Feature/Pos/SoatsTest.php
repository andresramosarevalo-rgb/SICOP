<?php

use App\Enums\FormaPago;
use App\Enums\Rol;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
});

function datosSoat(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'celular' => '3101234567',
        'correo' => 'carlos@example.com',
        'placa' => 'ABC123',
        'valor_soat' => '580000',
        'forma_pago' => 'efectivo',
        ...$sobrescribir,
    ];
}

// Issue #70, criterio 1
test('la sede registra un SOAT y este aparece en su listado', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat())
        ->assertRedirect(route('pos.sedes.soats.index', $this->sede));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Soats/Index')
            ->where('sede.id', $this->sede->id)
            ->has('soats', 1)
            ->where('soats.0.fecha', today()->toDateString())
            ->where('soats.0.valor_soat', '580000.00')
            ->where('soats.0.tipo_documento', 'CC')
            ->where('soats.0.numero_documento', '1006123456')
            ->where('soats.0.nombre_cliente', 'Carlos Pérez'));
});

// Issue #70, criterio 1
test('el listado de una sede no muestra los SOAT de otra', function () {
    Soat::factory()->create();
    Soat::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('soats', 1));
});

// Issue #70, criterio 2
test('los datos del SOAT son obligatorios', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), [])
        ->assertSessionHasErrors(['fecha', 'tipo_documento', 'numero_documento', 'nombre_cliente', 'celular', 'correo', 'placa', 'valor_soat', 'forma_pago']);

    expect(Soat::count())->toBe(0);
});

// Issue #70, criterio 2
test('el valor debe ser mayor que cero y la fecha no puede ser futura', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['valor_soat' => -5, 'fecha' => today()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['valor_soat', 'fecha']);
});

// Issue #70, criterio 3
test('una sede inactiva no puede registrar SOAT', function () {
    $sede = Sede::factory()->create(['es_activa' => false]);

    $this->actingAs($this->gerente)->get(route('pos.sedes.soats.create', $sede))->assertForbidden();
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $sede), datosSoat())
        ->assertForbidden();

    expect(Soat::count())->toBe(0);
});

// Issue #70, criterio 4
test('se pueden editar los datos de un SOAT', function () {
    $soat = Soat::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->put(route('pos.soats.update', $soat), datosSoat(['valor_soat' => '600000']))
        ->assertRedirect(route('pos.sedes.soats.index', $this->sede));

    expect($soat->fresh())
        ->valor_soat->toBe('600000.00')
        ->nombre_cliente->toBe('Carlos Pérez');
});

// Issue #67, criterio 2
test('el cajero recibe 403 en los SOAT', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.soats.index', $this->sede))->assertForbidden();
    $this->actingAs($cajero)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat())
        ->assertForbidden();
});

// Issue #70, criterio 2
test('el tipo de documento debe ser válido y el número solo lleva letras y números', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['tipo_documento' => 'XX', 'numero_documento' => '1.006 123']))
        ->assertSessionHasErrors(['tipo_documento', 'numero_documento']);
});

// Issue #74, criterio 4
test('el SOAT guarda el correo, el celular y la placa en mayúsculas', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['placa' => 'abc-123']))
        ->assertSessionHasNoErrors();

    expect(Soat::first())
        ->celular->toBe('3101234567')
        ->correo->toBe('carlos@example.com')
        ->placa->toBe('ABC123');
});

// Issue #74, criterio 4
test('la placa solo admite letras y números y el correo debe ser válido', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['placa' => 'AB*12', 'correo' => 'no-es-correo']))
        ->assertSessionHasErrors(['placa', 'correo']);
});

// Issue #74, criterio 1
test('con una forma de pago simple todo el valor va al medio elegido', function (string $forma, array $esperado) {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['forma_pago' => $forma]))
        ->assertSessionHasNoErrors();

    expect(Soat::first())
        ->forma_pago->toBe(FormaPago::from($forma))
        ->valor_efectivo->toBe($esperado[0])
        ->valor_qr->toBe($esperado[1]);
})->with([
    'efectivo' => ['efectivo', ['580000.00', '0.00']],
    'QR' => ['qr', ['0.00', '580000.00']],
]);

// Issue #126, criterio 1
test('el pago mixto del SOAT no cuenta un valor de datáfono que no acepta', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['forma_pago' => 'mixto', 'valor_efectivo' => 290000, 'valor_qr' => 0, 'valor_datafono' => 290000]))
        ->assertSessionHasErrors(['forma_pago' => 'Los valores por medio deben sumar el valor total.']);

    expect(Soat::count())->toBe(0);
});

// Issue #74, criterio 3
test('el SOAT no acepta pago por datáfono', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['forma_pago' => 'datafono']))
        ->assertSessionHasErrors('forma_pago');

    expect(Soat::count())->toBe(0);
});

// Issue #74, criterio 2
test('el pago mixto del SOAT debe sumar su valor', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['forma_pago' => 'mixto', 'valor_efectivo' => 280000, 'valor_qr' => 300000]))
        ->assertSessionHasNoErrors();

    expect(Soat::first())->valor_efectivo->toBe('280000.00')->valor_qr->toBe('300000.00');

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_qr' => 100000]))
        ->assertSessionHasErrors(['forma_pago' => 'Los valores por medio deben sumar el valor total.']);
});

// Issue #74, criterio 3
test('el formulario del SOAT ofrece efectivo, QR y mixto', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.create', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->where('formasPago.0.valor', 'efectivo')
            ->where('formasPago.1.valor', 'qr')
            ->where('formasPago.2.valor', 'mixto')
            ->has('formasPago', 3));
});

// Issue #74, criterio 5
test('el listado de SOAT muestra la forma de pago de cada registro', function () {
    Soat::factory()->for($this->sede)->create(['forma_pago' => 'qr', 'valor_efectivo' => 0, 'valor_qr' => 580000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->where('soats.0.forma_pago', 'qr')
            ->where('soats.0.valor_qr', '580000.00'));
});

test('el listado muestra los SOAT de la sede, los más recientes primero', function () {
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-14', 'nombre_cliente' => 'Antiguo', 'valor_soat' => 580000]);
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => 'Reciente']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Soats/Index')
            ->where('sede.id', $this->sede->id)
            ->has('soats', 2)
            ->where('soats.0.nombre_cliente', 'Reciente')
            ->where('soats.1.nombre_cliente', 'Antiguo')
            ->where('soats.1.valor_soat', '580000.00'));
});
