<?php

namespace App\Actions\Nomina;

use Carbon\CarbonImmutable;

/**
 * Calendario exclusivo para el consumo de vacaciones; no cambia la liquidación monetaria.
 */
final class CalendarioVacaciones
{
    /**
     * Cuenta las fechas hábiles de un intervalo inclusivo según la configuración disponible.
     */
    public function contarDias(CarbonImmutable $desde, CarbonImmutable $hasta): int
    {
        /** @var list<int> $diasHabiles */
        $diasHabiles = config('vacaciones.dias_habiles', [1, 2, 3, 4, 5, 6]);
        /** @var list<string> $festivos */
        $festivos = config('vacaciones.festivos', []);
        $fechasExcluidas = array_fill_keys($festivos, true);
        if (config('vacaciones.excluir_festivos_colombianos', true)) {
            for ($anio = $desde->year; $anio <= $hasta->year; $anio++) {
                $fechasExcluidas += array_fill_keys((new FestivosColombia)->fechas($anio), true);
            }
        }
        $dias = 0;

        for ($fecha = $desde; $fecha->lte($hasta); $fecha = $fecha->addDay()) {
            if (in_array($fecha->dayOfWeekIso, $diasHabiles, true)
                && ! isset($fechasExcluidas[$fecha->toDateString()])) {
                $dias++;
            }
        }

        return $dias;
    }

    public function criterio(): string
    {
        /** @var list<int> $diasHabiles */
        $diasHabiles = config('vacaciones.dias_habiles', [1, 2, 3, 4, 5, 6]);
        /** @var list<string> $festivos */
        $festivos = config('vacaciones.festivos', []);
        $nombres = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $dias = array_map(fn (int $dia): string => $nombres[$dia], $diasHabiles);

        $criterioFestivos = config('vacaciones.excluir_festivos_colombianos', true)
            ? 'Se excluyen los festivos nacionales de Colombia. '
            : '';

        return 'Días considerados: '.implode(', ', $dias).'. '
            .$criterioFestivos
            .($festivos === [] ? '' : 'Se excluyen también las fechas adicionales configuradas.');
    }

    /**
     * @return list<string>
     */
    public function advertencias(): array
    {
        if (config('vacaciones.es_calendario_confirmado', false)) {
            return [];
        }

        return ['El consumo es una estimación: el calendario de vacaciones no está confirmado. '
            .'Los festivos no configurados y las jornadas especiales pueden cambiar los días utilizados y disponibles.'];
    }
}
