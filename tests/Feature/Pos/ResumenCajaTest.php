<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create(['valor_base_caja' => 200000]);
});

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
            ->where('valorEfectivoEsperado', 850000));
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
