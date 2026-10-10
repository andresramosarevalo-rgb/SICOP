<?php

use App\Enums\PeriodicidadPago;
use App\Enums\Rol;
use App\Enums\TipoContrato;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\User;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create();
});

// Issue #8, criterio 1
test('un empleado sin contrato vigente queda con el contrato registrado como vigente', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.contratos.store', $this->empleado), datosContrato())
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));

    $contrato = $this->empleado->contratoVigente()->sole();
    expect($contrato->tipo_contrato)->toBe(TipoContrato::TerminoIndefinido)
        ->and($contrato->periodicidad_pago)->toBe(PeriodicidadPago::Quincenal)
        ->and($contrato->fecha_inicio->toDateString())->toBe('2026-02-01')
        ->and($contrato->valor_salario_base)->toBe('2000000.00');
});

// Issue #8, criterio 2
test('el contrato nuevo debe iniciar después del contrato vigente', function () {
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-03-01']);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.contratos.store', $this->empleado), datosContrato(['fecha_inicio' => '2026-02-01']))
        ->assertSessionHasErrors(['fecha_inicio' => 'Debe ser posterior al inicio del contrato vigente.']);

    expect($this->empleado->contratos()->count())->toBe(1);
});

// Issue #8, criterio 3
test('un contrato a término fijo exige una fecha de fin posterior al inicio', function (?string $fechaFin, string $mensaje) {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.contratos.store', $this->empleado), datosContrato([
            'tipo_contrato' => TipoContrato::TerminoFijo->value,
            'fecha_fin' => $fechaFin,
        ]))
        ->assertSessionHasErrors(['fecha_fin' => $mensaje]);

    expect(Contrato::count())->toBe(0);
})->with([
    'sin fecha de fin' => [null, 'Un contrato a término fijo necesita fecha de fin.'],
    'fecha de fin anterior al inicio' => ['2026-01-15', 'La fecha de fin debe ser posterior a la fecha de inicio.'],
]);
