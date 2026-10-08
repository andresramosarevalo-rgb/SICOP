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

// Issue #10, criterio 1
test('al crear un concepto de valor fijo aparece en el listado', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.conceptos.store'), [
            'codigo' => 'BONO_VENTAS',
            'nombre' => 'Bono por ventas',
            'tipo' => 'devengo',
            'forma_calculo' => 'valor_fijo',
            'valor_base' => '150000',
            'es_constitutivo_salario' => '0',
        ])
        ->assertRedirect(route('nomina.conceptos.index'));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.conceptos.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Conceptos/Index')
            ->has('conceptos', 1)
            ->where('conceptos.0.codigo', 'BONO_VENTAS')
            ->where('conceptos.0.valor_base', '150000.00'));
});

// Issue #10, criterio 1
test('un concepto de porcentaje exige el porcentaje', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.conceptos.store'), [
            'codigo' => 'LIBRANZA',
            'nombre' => 'Libranza',
            'tipo' => 'deduccion',
            'forma_calculo' => 'porcentaje',
            'es_constitutivo_salario' => '0',
        ])
        ->assertSessionHasErrors(['porcentaje_base' => 'Indique el porcentaje del concepto.']);
});

// Issue #10, criterio 2
test('un concepto de sistema no se puede eliminar ni editar', function () {
    $salario = ConceptoNomina::factory()->create([
        'codigo' => ConceptoSistema::Salario->value,
        'forma_calculo' => FormaCalculo::Sistema,
        'es_sistema' => true,
    ]);

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.conceptos.destroy', $salario))
        ->assertForbidden();

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.conceptos.update', $salario), ['codigo' => 'OTRO'])
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

// Issue #10, criterio 3
test('no se puede crear un concepto con un código que ya existe', function () {
    ConceptoNomina::factory()->create(['codigo' => 'BONO_VENTAS']);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.conceptos.store'), [
            'codigo' => 'BONO_VENTAS',
            'nombre' => 'Otro bono',
            'tipo' => 'devengo',
            'forma_calculo' => 'valor_fijo',
            'valor_base' => '1000',
            'es_constitutivo_salario' => '0',
        ])
        ->assertSessionHasErrors(['codigo' => 'Ya existe un concepto con ese código.']);

    expect(ConceptoNomina::count())->toBe(1);
});
