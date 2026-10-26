<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\ParametroNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Rutas;

/*
 * Instrumento del atributo ACQ-02 (confidencialidad de los datos de nómina) del plan de calidad:
 * recorre todas las rutas con nombre nomina.* y mide cuántas están protegidas.
 */

/**
 * Rutas del módulo de nómina con un modelo real para cada parámetro, para que el enlace de
 * modelos no responda 404 antes de llegar a la autorización.
 *
 * @return list<array{string, string, string}> [nombre, método, URL]
 */
function rutasDeNomina(): array
{
    $recibo = ReciboNomina::factory()->create();
    $modelos = [
        'area' => Area::factory()->create(),
        'asignacion' => AsignacionConcepto::factory()->create(),
        'concepto' => ConceptoNomina::factory()->create(),
        'empleado' => Empleado::factory()->create(),
        'novedad' => Novedad::factory()->create(),
        'parametro' => ParametroNomina::factory()->create(),
        'periodo' => $recibo->periodo,
        'recibo' => $recibo,
    ];

    return collect(Rutas::getRoutes()->getRoutes())
        ->filter(fn (Route $ruta) => str_starts_with((string) $ruta->getName(), 'nomina.'))
        ->map(fn (Route $ruta) => [
            $ruta->getName(),
            collect($ruta->methods())->reject(fn (string $metodo) => $metodo === 'HEAD')->first(),
            route($ruta->getName(), collect($ruta->parameterNames())->mapWithKeys(fn (string $parametro) => [$parametro => $modelos[$parametro]])->all()),
        ])
        ->values()
        ->all();
}

// ACQ-02 · issue #4, criterios 2 y 3
test('el 100 % de las rutas de nómina responde 403 al cajero y redirige al visitante al login', function () {
    $rutas = rutasDeNomina();
    $cajero = User::factory()->conRol(Rol::Cajero)->create();
    $expuestas = [];

    foreach ($rutas as [$nombre, $metodo, $url]) {
        $this->app['auth']->forgetGuards();
        $comoVisitante = $this->call($metodo, $url);

        $comoCajero = $this->actingAs($cajero)->call($metodo, $url);

        if (! $comoVisitante->isRedirect(route('login')) || $comoCajero->getStatusCode() !== 403) {
            $expuestas[] = "{$metodo} {$nombre}: visitante {$comoVisitante->getStatusCode()}, cajero {$comoCajero->getStatusCode()}";
        }
    }

    expect($rutas)->toHaveCount(41)
        ->and($expuestas)->toBe([]);
});
