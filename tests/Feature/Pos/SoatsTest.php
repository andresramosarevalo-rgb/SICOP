<?php

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
        'valor_soat' => '580000',
        ...$sobrescribir,
    ];
}

// Issue #70, criterio 1
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

// Issue #70, criterio 1
test('el listado de una sede no muestra los SOAT de otra', function () {
    Soat::factory()->create();
    Soat::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('soats', 1));
});

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

// Issue #70, criterio 2
test('los datos del SOAT son obligatorios', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), [])
        ->assertSessionHasErrors(['fecha', 'tipo_documento', 'numero_documento', 'nombre_cliente', 'valor_soat']);

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

// Issue #70, criterio 2
test('el tipo de documento debe ser válido y el número solo lleva letras y números', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat(['tipo_documento' => 'XX', 'numero_documento' => '1.006 123']))
        ->assertSessionHasErrors(['tipo_documento', 'numero_documento']);
});
// Issue #67, criterio 2
test('el cajero recibe 403 en los SOAT', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.soats.index', $this->sede))->assertForbidden();
    $this->actingAs($cajero)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoat())
        ->assertForbidden();
});
