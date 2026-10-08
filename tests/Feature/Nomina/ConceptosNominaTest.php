<?php

use App\Enums\ConceptoSistema;
use App\Enums\FormaCalculo;
use App\Enums\Rol;
use App\Enums\TipoConcepto;
use App\Models\ConceptoNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #10, criterio 2
test('la base de datos sembrada tiene los conceptos de sistema', function () {
    $this->seed(NominaSeeder::class);

    expect(ConceptoNomina::where('es_sistema', true)->pluck('codigo')->sort()->values()->all())
        ->toBe(collect(ConceptoSistema::cases())->pluck('value')->sort()->values()->all());

    $salud = ConceptoNomina::firstWhere('codigo', ConceptoSistema::Salud->value);
    $auxilio = ConceptoNomina::firstWhere('codigo', ConceptoSistema::AuxilioTransporte->value);
    expect($salud->tipo)->toBe(TipoConcepto::Deduccion)
        ->and($auxilio->tipo)->toBe(TipoConcepto::Devengo)
        ->and($auxilio->es_constitutivo_salario)->toBeFalse();
});

// Issue #10, criterio 1
test('el listado muestra primero los devengos y luego las deducciones', function () {
    ConceptoNomina::factory()->create(['nombre' => 'Libranza', 'tipo' => TipoConcepto::Deduccion]);
    ConceptoNomina::factory()->create(['nombre' => 'Bono', 'tipo' => TipoConcepto::Devengo]);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.conceptos.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Conceptos/Index')
            ->where('conceptos.0.nombre', 'Bono')
            ->where('conceptos.1.nombre', 'Libranza'));
});

// Issue #10, criterio 2
test('un concepto de sistema no se puede eliminar', function () {
    $salario = ConceptoNomina::factory()->create([
        'codigo' => ConceptoSistema::Salario->value,
        'forma_calculo' => FormaCalculo::Sistema,
        'es_sistema' => true,
    ]);

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.conceptos.destroy', $salario))
        ->assertForbidden();

    expect($salario->fresh())->not->toBeNull()
        ->and($salario->fresh()->codigo)->toBe('SALARIO');
});

// Issue #10, criterio 2
test('un concepto que no es de sistema sí se puede eliminar', function () {
    $bono = ConceptoNomina::factory()->create();

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.conceptos.destroy', $bono))
        ->assertRedirect(route('nomina.conceptos.index'));

    expect($bono->fresh())->toBeNull();
});
