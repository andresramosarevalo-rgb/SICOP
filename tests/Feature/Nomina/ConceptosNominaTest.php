<?php

use App\Enums\ConceptoSistema;
use App\Enums\TipoConcepto;
use App\Models\ConceptoNomina;
use Database\Seeders\NominaSeeder;

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
