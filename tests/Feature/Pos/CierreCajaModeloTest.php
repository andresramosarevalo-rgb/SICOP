<?php

use App\Enums\EstadoCierre;
use App\Models\CierreCaja;
use App\Models\GastoCaja;
use App\Models\Sede;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Issue #77, criterio 1
test('un cierre cerrado se distingue de uno abierto', function () {
    $cerrado = CierreCaja::factory()->cerrado()->create();
    $abierto = CierreCaja::factory()->create();

    expect($cerrado->estaCerrado())->toBeTrue()
        ->and($cerrado->estado)->toBe(EstadoCierre::Cerrado)
        ->and($abierto->estaCerrado())->toBeFalse()
        ->and($abierto->estado)->toBe(EstadoCierre::Abierto);
});

// Issue #76, criterio 3
test('un cierre nuevo está abierto y reúne los gastos del día de su sede', function () {
    $cierre = CierreCaja::factory()->create();
    GastoCaja::factory()->count(2)->for($cierre)->create();

    expect($cierre->estaCerrado())->toBeFalse()
        ->and($cierre->gastos)->toHaveCount(2)
        ->and($cierre->gastos->first()->cierreCaja->is($cierre))->toBeTrue()
        ->and($cierre->sede->cierresCaja->first()->is($cierre))->toBeTrue();
});

// Issue #77, criterio 3
test('una sede tiene un solo cierre por día', function () {
    $sede = Sede::factory()->create();
    CierreCaja::factory()->for($sede)->create(['fecha' => '2026-10-15']);

    expect(fn () => CierreCaja::factory()->for($sede)->create(['fecha' => '2026-10-15']))->toThrow(QueryException::class);
});

// Issue #76, criterio 2
test('la base de caja de una sede es un valor con dos decimales', function () {
    expect(Sede::factory()->create(['valor_base_caja' => 200000])->fresh()->valor_base_caja)->toBe('200000.00');
});

// Issue #127, criterio 1
test('exigir el día abierto consulta la sede para bloquearla y rechaza un día cerrado', function () {
    $sede = Sede::factory()->create();
    CierreCaja::factory()->cerrado()->for($sede)->create(['fecha' => '2026-02-10']);

    $consultas = [];
    DB::listen(function ($consulta) use (&$consultas) {
        $consultas[] = $consulta->sql;
    });

    CierreCaja::exigirDiaAbierto($sede->id, '2026-02-11');

    expect(collect($consultas)->contains(fn (string $sql) => str_contains($sql, 'from "sedes"')))->toBeTrue()
        ->and(fn () => CierreCaja::exigirDiaAbierto($sede->id, '2026-02-11', '2026-02-10'))->toThrow(HttpException::class);
});
