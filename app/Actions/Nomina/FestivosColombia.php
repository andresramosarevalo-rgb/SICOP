<?php

namespace App\Actions\Nomina;

use Carbon\CarbonImmutable;

/**
 * Festivos nacionales: Ley 51 de 1983 y, desde 2026, artículo 6 de la Ley 2578.
 * Las fechas trasladables excluyen el lunes de descanso, no la celebración original.
 */
final class FestivosColombia
{
    /** @return list<string> */
    public function fechas(int $anio): array
    {
        $fechas = [];
        foreach ([[1, 1], [5, 1], [7, 20], [8, 7], [12, 8], [12, 25]] as [$mes, $dia]) {
            $fechas[] = CarbonImmutable::createMidnightDate($anio, $mes, $dia)->toDateString();
        }

        $trasladables = [[1, 6], [3, 19], [6, 29], [8, 15], [10, 12], [11, 1], [11, 11]];
        if ($anio >= 2026) {
            $trasladables[] = [7, 9];
        }

        foreach ($trasladables as [$mes, $dia]) {
            $fecha = CarbonImmutable::createMidnightDate($anio, $mes, $dia);
            $fechas[] = $fecha->addDays((8 - $fecha->dayOfWeekIso) % 7)->toDateString();
        }

        $pascua = $this->pascua($anio);
        // Jueves y Viernes Santo; lunes de Ascensión, Corpus Christi y Sagrado Corazón.
        foreach ([-3, -2, 43, 64, 71] as $desplazamiento) {
            $fechas[] = $pascua->addDays($desplazamiento)->toDateString();
        }

        $fechas = array_values(array_unique($fechas));
        sort($fechas);

        return $fechas;
    }

    /**
     * Computus gregoriano de Meeus/Jones/Butcher, con aritmética entera.
     * No depende de timestamps, de la zona horaria del servidor ni de ext-calendar.
     */
    private function pascua(int $anio): CarbonImmutable
    {
        $cicloLunar = $anio % 19;
        $siglo = intdiv($anio, 100);
        $restoSiglo = $anio % 100;
        $correccionSolar = intdiv($siglo, 4);
        $restoSolar = $siglo % 4;
        $correccionLunar = intdiv($siglo + 8, 25);
        $ajusteLunar = intdiv($siglo - $correccionLunar + 1, 3);
        $epacta = (19 * $cicloLunar + $siglo - $correccionSolar - $ajusteLunar + 15) % 30;
        $bisiestos = intdiv($restoSiglo, 4);
        $restoBisiestos = $restoSiglo % 4;
        $ajusteSemanal = (32 + 2 * $restoSolar + 2 * $bisiestos - $epacta - $restoBisiestos) % 7;
        $correccion = intdiv($cicloLunar + 11 * $epacta + 22 * $ajusteSemanal, 451);
        $base = $epacta + $ajusteSemanal - 7 * $correccion + 114;

        return CarbonImmutable::createMidnightDate($anio, intdiv($base, 31), $base % 31 + 1);
    }
}
