<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCierre;
use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\Soat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Cantidad de meses, contando el actual, que muestra la tendencia de ventas.
     */
    private const MESES_TENDENCIA = 6;

    /**
     * Muestra los indicadores de cada módulo al que el usuario tiene acceso; el cajero va a su punto de venta.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->rol === Rol::Cajero) {
            return to_route('pos.inicio.index');
        }

        return Inertia::render('Dashboard', [
            'pos' => $usuario->can('gestionar-pos') ? $this->indicadoresPos($usuario) : null,
            'nomina' => $usuario->can('gestionar-nomina') ? $this->indicadoresNomina($usuario) : null,
        ]);
    }

    /**
     * Ventas del mes, saldo por consignar y tendencia de ventas de las sedes que el usuario puede ver.
     *
     * @return array{
     *     mes: string,
     *     cursos: array{cantidad: int, valor: string},
     *     soats: array{cantidad: int, valor: string},
     *     cierres_cerrados_mes: int,
     *     saldo_por_consignar: string,
     *     dias_sin_consignar: int,
     *     tendencia: list<array{mes: string, cursos: string, soats: string}>
     * }
     */
    private function indicadoresPos(User $usuario): array
    {
        $inicioMes = today()->startOfMonth();
        $finMes = today()->endOfMonth();

        $cursos = Curso::query()->visiblesPara($usuario)->whereBetween('fecha', [$inicioMes, $finMes]);
        $soats = Soat::query()->visiblesPara($usuario)->whereBetween('fecha', [$inicioMes, $finMes]);

        $saldos = CierreCaja::query()
            ->visiblesPara($usuario)
            ->leftJoin('consignaciones', 'consignaciones.cierre_caja_id', '=', 'cierres_caja.id')
            ->where('cierres_caja.estado', EstadoCierre::Cerrado)
            ->selectRaw('coalesce(sum(cierres_caja.valor_a_consignar), 0) as valor_a_consignar')
            ->selectRaw('coalesce(sum(consignaciones.valor_consignado), 0) as valor_consignado')
            ->selectRaw('coalesce(sum(case when consignaciones.id is null and cierres_caja.valor_a_consignar > 0 then 1 else 0 end), 0) as dias_sin_consignar')
            ->toBase()
            ->first();

        return [
            'mes' => $inicioMes->toDateString(),
            'cursos' => ['cantidad' => (clone $cursos)->count(), 'valor' => $this->pesos($cursos->sum('valor_curso'))],
            'soats' => ['cantidad' => (clone $soats)->count(), 'valor' => $this->pesos($soats->sum('valor_soat'))],
            'cierres_cerrados_mes' => CierreCaja::query()->visiblesPara($usuario)
                ->where('estado', EstadoCierre::Cerrado)
                ->whereBetween('fecha', [$inicioMes, $finMes])
                ->count(),
            'saldo_por_consignar' => $this->pesos((float) $saldos->valor_a_consignar - (float) $saldos->valor_consignado),
            'dias_sin_consignar' => (int) $saldos->dias_sin_consignar,
            'tendencia' => $this->tendenciaDeVentas($usuario),
        ];
    }

    /**
     * Valor vendido en cursos y SOAT en cada uno de los últimos meses, del más antiguo al actual.
     *
     * @return list<array{mes: string, cursos: string, soats: string}>
     */
    private function tendenciaDeVentas(User $usuario): array
    {
        $desde = today()->startOfMonth()->subMonths(self::MESES_TENDENCIA - 1);

        $cursos = Curso::query()->visiblesPara($usuario)->where('fecha', '>=', $desde)
            ->selectRaw("to_char(fecha, 'YYYY-MM') as mes, sum(valor_curso) as valor")
            ->groupBy('mes')->toBase()->pluck('valor', 'mes');
        $soats = Soat::query()->visiblesPara($usuario)->where('fecha', '>=', $desde)
            ->selectRaw("to_char(fecha, 'YYYY-MM') as mes, sum(valor_soat) as valor")
            ->groupBy('mes')->toBase()->pluck('valor', 'mes');

        return array_values(collect(range(0, self::MESES_TENDENCIA - 1))
            ->map(fn (int $desplazamiento): CarbonInterface => $desde->addMonths($desplazamiento))
            ->map(fn (CarbonInterface $mes): array => [
                'mes' => $mes->toDateString(),
                'cursos' => $this->pesos($cursos->get($mes->format('Y-m'), 0)),
                'soats' => $this->pesos($soats->get($mes->format('Y-m'), 0)),
            ])
            ->all());
    }

    /**
     * Empleados activos, periodos sin cerrar y novedades pendientes de liquidar de las sedes que el usuario puede ver.
     *
     * @return array{empleados_activos: int, periodos_abiertos: int, novedades_sin_liquidar: int}
     */
    private function indicadoresNomina(User $usuario): array
    {
        return [
            'empleados_activos' => Empleado::query()->visiblesPara($usuario)->where('es_activo', true)->count(),
            'periodos_abiertos' => PeriodoNomina::query()->visiblesPara($usuario)
                ->where('estado', '!=', EstadoPeriodo::Cerrado)
                ->count(),
            'novedades_sin_liquidar' => Novedad::query()->visiblesPara($usuario)->whereNull('periodo_nomina_id')->count(),
        ];
    }

    /**
     * Valor monetario con dos decimales, como lo entrega el resto de la aplicación.
     */
    private function pesos(float|int|string|null $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
