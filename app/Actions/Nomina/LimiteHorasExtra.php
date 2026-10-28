<?php

namespace App\Actions\Nomina;

use App\Enums\TipoNovedad;
use App\Models\Novedad;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Máximo legal de horas extra de un empleado: 2 por día y 12 por semana (de lunes a domingo), sumando
 * las diurnas y las nocturnas. Al editar una novedad no se cuenta la propia novedad.
 */
final class LimiteHorasExtra
{
    public const MAXIMO_DIA = 2;

    public const MAXIMO_SEMANA = 12;

    /**
     * Motivo por el que las horas pasan del máximo, con las que le quedan al empleado, o null si caben.
     */
    public function motivoDeRechazo(int $empleadoId, CarbonImmutable $fecha, int $horas, ?Novedad $editada = null): ?string
    {
        $horasEnElDia = $this->horasRegistradas($empleadoId, $fecha, $fecha, $editada);

        if ($horasEnElDia + $horas > self::MAXIMO_DIA) {
            return 'Supera el máximo de '.self::MAXIMO_DIA.' horas extra por día; al empleado le '
                .$this->restantes(self::MAXIMO_DIA - $horasEnElDia).' ese día.';
        }

        $horasEnLaSemana = $this->horasRegistradas(
            $empleadoId,
            $fecha->startOfWeek(CarbonInterface::MONDAY),
            $fecha->endOfWeek(CarbonInterface::SUNDAY),
            $editada,
        );

        if ($horasEnLaSemana + $horas > self::MAXIMO_SEMANA) {
            return 'Supera el máximo de '.self::MAXIMO_SEMANA.' horas extra por semana (de lunes a domingo); al empleado le '
                .$this->restantes(self::MAXIMO_SEMANA - $horasEnLaSemana).' esa semana.';
        }

        return null;
    }

    /**
     * Horas extra que el empleado ya tiene entre dos fechas, sin contar la novedad que se está editando.
     */
    private function horasRegistradas(int $empleadoId, CarbonImmutable $desde, CarbonImmutable $hasta, ?Novedad $editada): int
    {
        return (int) Novedad::query()
            ->where('empleado_id', $empleadoId)
            ->whereIn('tipo', [TipoNovedad::HoraExtraDiurna, TipoNovedad::HoraExtraNocturna])
            ->whereDate('fecha_inicio', '>=', $desde)
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->when($editada, fn ($query) => $query->whereKeyNot($editada->getKey()))
            ->sum('cantidad');
    }

    private function restantes(int $horas): string
    {
        $horas = max(0, $horas);

        return $horas === 1 ? 'queda 1 hora' : "quedan {$horas} horas";
    }
}
