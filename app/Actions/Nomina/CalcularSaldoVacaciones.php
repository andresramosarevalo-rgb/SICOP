<?php

namespace App\Actions\Nomina;

use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use BcMath\Number;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use RoundingMode;

/**
 * Saldo calculado a partir de contratos y novedades registrados, sin guardar movimientos.
 * Los días de servicio causan 15 días por año cumplido y una fracción del siguiente año.
 * El consumo usa su propio calendario, independiente de los días pagados en nómina.
 */
final class CalcularSaldoVacaciones
{
    public function __construct(private CalendarioVacaciones $calendario) {}

    /**
     * Consulta las relaciones de nuevo para reflejar cambios incluso sobre el mismo empleado cargado.
     * Las fechas de inicio, terminación y corte se incluyen; nunca se usa created_at como ingreso.
     * La exclusión y la propuesta simulan una edición sin escribir ni duplicar su consumo.
     *
     * @return array{fechaCorte: string, fechaIngreso: ?string, diasCausados: string, diasUtilizados: int, diasDisponibles: string, criterioCalendario: string, advertencias: list<string>}
     */
    public function ejecutar(
        Empleado $empleado,
        ?CarbonInterface $fechaCorte = null,
        ?int $novedadExcluidaId = null,
        ?Novedad $propuesta = null,
    ): array {
        /** @var string $zonaHoraria */
        $zonaHoraria = config('vacaciones.zona_horaria', 'America/Bogota');
        $corte = CarbonImmutable::parse(
            $fechaCorte?->toDateString() ?? CarbonImmutable::now($zonaHoraria)->toDateString(),
            $zonaHoraria,
        )->startOfDay();
        $advertencias = [
            'Saldo calculado con los contratos y las novedades registrados en SICOP. '
            .'No certifica el historial completo ni incluye saldos iniciales o compensaciones anteriores.',
            ...$this->calendario->advertencias(),
        ];
        $servicio = [];

        $contratos = $empleado->contratos()
            ->whereDate('fecha_inicio', '<=', $corte->toDateString())
            ->get(['id', 'fecha_inicio', 'fecha_fin', 'es_vigente']);

        foreach ($contratos as $contrato) {
            if (! $contrato->es_vigente && $contrato->fecha_fin === null) {
                $advertencias[] = 'Hay contratos no vigentes sin fecha de terminación; se excluyeron del cálculo.';

                continue;
            }

            $desde = CarbonImmutable::parse($contrato->fecha_inicio->toDateString(), $zonaHoraria);
            $hasta = $contrato->fecha_fin === null
                ? $corte
                : CarbonImmutable::parse($contrato->fecha_fin->toDateString(), $zonaHoraria)->min($corte);

            if ($hasta->lt($desde)) {
                $advertencias[] = 'Hay contratos con fechas inconsistentes; se excluyeron del cálculo.';

                continue;
            }

            $servicio[] = ['desde' => $desde, 'hasta' => $hasta];
        }

        $servicio = $this->unirIntervalos($servicio);
        $causados = new Number('0');

        foreach ($servicio as $intervalo) {
            $causados = $causados->add($this->causar($intervalo['desde'], $intervalo['hasta']), 12);
        }

        $novedades = $empleado->novedades()
            ->where('tipo', TipoNovedad::Vacaciones)
            ->whereDate('fecha_inicio', '<=', $corte->toDateString())
            ->when($novedadExcluidaId !== null, fn ($query) => $query->whereKeyNot($novedadExcluidaId))
            ->get(['id', 'fecha_inicio', 'fecha_fin']);

        if ($propuesta !== null && $propuesta->empleado_id === $empleado->id
            && $propuesta->tipo === TipoNovedad::Vacaciones
            && $propuesta->fecha_inicio->toDateString() <= $corte->toDateString()) {
            $novedades->push($propuesta);
        }

        $vacaciones = [];

        foreach ($novedades as $novedad) {
            $desde = CarbonImmutable::parse($novedad->fecha_inicio->toDateString(), $zonaHoraria);
            $hasta = CarbonImmutable::parse(
                ($novedad->fecha_fin ?? $novedad->fecha_inicio)->toDateString(),
                $zonaHoraria,
            )->min($corte);

            if ($hasta->lt($desde)) {
                $advertencias[] = 'Hay novedades de vacaciones con fechas inconsistentes; se excluyeron del cálculo.';

                continue;
            }

            foreach ($servicio as $intervalo) {
                $inicio = $desde->max($intervalo['desde']);
                $fin = $hasta->min($intervalo['hasta']);

                if ($inicio->lte($fin)) {
                    $vacaciones[] = ['desde' => $inicio, 'hasta' => $fin];
                }
            }
        }

        $utilizados = 0;

        foreach ($this->unirIntervalos($vacaciones) as $intervalo) {
            $utilizados += $this->calendario->contarDias($intervalo['desde'], $intervalo['hasta']);
        }

        return [
            'fechaCorte' => $corte->toDateString(),
            'fechaIngreso' => isset($servicio[0]) ? $servicio[0]['desde']->toDateString() : null,
            'diasCausados' => $this->formatearDias($causados),
            'diasUtilizados' => $utilizados,
            'diasDisponibles' => $this->formatearDias($causados->sub($utilizados, 12)),
            'criterioCalendario' => $this->calendario->criterio(),
            'advertencias' => array_values(array_unique($advertencias)),
        ];
    }

    /**
     * Cada aniversario se obtiene desde el origen para no arrastrar el ajuste del 29 de febrero.
     * Se suma el último día de servicio convirtiendo el fin inclusivo en exclusivo.
     */
    private function causar(CarbonImmutable $desde, CarbonImmutable $hasta): Number
    {
        $finExclusivo = $hasta->addDay();
        $anios = 0;

        while ($desde->addYearsNoOverflow($anios + 1)->lte($finExclusivo)) {
            $anios++;
        }

        $ultimoAniversario = $desde->addYearsNoOverflow($anios);
        $proximoAniversario = $desde->addYearsNoOverflow($anios + 1);
        $diasProporcionales = (int) $ultimoAniversario->diffInDays($finExclusivo);
        $diasAnio = (int) $ultimoAniversario->diffInDays($proximoAniversario);

        return (new Number($anios * 15))->add(
            (new Number($diasProporcionales * 15))->div($diasAnio, 12),
            12,
        );
    }

    /**
     * @param  list<array{desde: CarbonImmutable, hasta: CarbonImmutable}>  $intervalos
     * @return list<array{desde: CarbonImmutable, hasta: CarbonImmutable}>
     */
    private function unirIntervalos(array $intervalos): array
    {
        usort($intervalos, fn (array $primero, array $segundo): int => $primero['desde']->getTimestamp() <=> $segundo['desde']->getTimestamp());
        /** @var list<array{desde: CarbonImmutable, hasta: CarbonImmutable}> $unidos */
        $unidos = [];

        foreach ($intervalos as $intervalo) {
            $ultimo = count($unidos) - 1;

            if ($ultimo >= 0 && $intervalo['desde']->lte($unidos[$ultimo]['hasta']->addDay())) {
                $unidos[$ultimo] = [
                    'desde' => $unidos[$ultimo]['desde'],
                    'hasta' => $unidos[$ultimo]['hasta']->max($intervalo['hasta']),
                ];
            } else {
                $unidos[] = $intervalo;
            }
        }

        return $unidos;
    }

    private function formatearDias(Number $dias): string
    {
        return (string) $dias->round(4, RoundingMode::HalfAwayFromZero)->add('0', 4);
    }
}
