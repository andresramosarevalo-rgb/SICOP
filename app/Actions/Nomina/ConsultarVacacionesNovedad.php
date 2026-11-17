<?php

namespace App\Actions\Nomina;

use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use BcMath\Number;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ConsultarVacacionesNovedad
{
    public function __construct(
        private CalcularSaldoVacaciones $calcularSaldo,
        private CalendarioVacaciones $calendario,
    ) {}

    /**
     * Causa solo hasta hoy y reserva el consumo de todas las vacaciones registradas.
     * La propuesta se simula sin persistir; la edición excluye primero su registro original.
     *
     * @return array{saldoVacaciones: array{fechaCorte: string, fechaIngreso: ?string, diasCausados: string, diasUtilizados: int, diasDisponibles: string, criterioCalendario: string, advertencias: list<string>}, diasProgramados: int, diasDisponiblesParaProgramar: string, diasSolicitados: ?int, diasADescontar: ?int, diasRestantes: ?string, tieneSaldoSuficiente: ?bool}
     */
    public function ejecutar(Empleado $empleado, ?Novedad $propuesta = null, ?Novedad $editada = null): array
    {
        $excluidaId = $editada?->id;
        $saldo = $this->calcularSaldo->ejecutar($empleado, novedadExcluidaId: $excluidaId);
        /** @var string $zonaHoraria */
        $zonaHoraria = config('vacaciones.zona_horaria', 'America/Bogota');
        $hoy = CarbonImmutable::parse($saldo['fechaCorte'], $zonaHoraria);
        // Incluye la fecha original aunque se edite para comparar ambos consumos al mismo corte.
        /** @var string|null $ultimaFecha */
        $ultimaFecha = $empleado->novedades()
            ->where('tipo', TipoNovedad::Vacaciones)
            ->max(DB::raw('COALESCE(fecha_fin, fecha_inicio)'));
        $corteConsumo = CarbonImmutable::parse($ultimaFecha ?? $saldo['fechaCorte'], $zonaHoraria)->max($hoy);

        if ($propuesta !== null) {
            $corteConsumo = $corteConsumo->max(CarbonImmutable::parse(
                ($propuesta->fecha_fin ?? $propuesta->fecha_inicio)->toDateString(), $zonaHoraria,
            ));
        }

        // Del corte futuro se usa únicamente el consumo; nunca su causación proyectada.
        $sinPropuesta = $corteConsumo->eq($hoy)
            ? $saldo
            : $this->calcularSaldo->ejecutar($empleado, $corteConsumo, $excluidaId);
        /** @var numeric-string $causados */
        $causados = $saldo['diasCausados'];
        $disponibles = (new Number($causados))->sub($sinPropuesta['diasUtilizados'], 4);
        $solicitados = null;
        $descontar = null;
        $tieneEdicionSinAumento = false;

        if ($propuesta !== null) {
            $solicitados = $this->calendario->contarDias(
                CarbonImmutable::parse($propuesta->fecha_inicio->toDateString(), $zonaHoraria),
                CarbonImmutable::parse(($propuesta->fecha_fin ?? $propuesta->fecha_inicio)->toDateString(), $zonaHoraria),
            );
            $conPropuesta = $this->calcularSaldo->ejecutar($empleado, $corteConsumo, $excluidaId, $propuesta);
            $descontar = $conPropuesta['diasUtilizados'] - $sinPropuesta['diasUtilizados'];

            if ($editada !== null && $editada->empleado_id === $empleado->id
                && $editada->tipo === TipoNovedad::Vacaciones) {
                // Compara el total con la original y con su reemplazo al mismo horizonte.
                // Mantiene solapamientos y reservas; no transfiere crédito entre empleados.
                $antesDeEditar = $this->calcularSaldo->ejecutar($empleado, $corteConsumo);
                $tieneEdicionSinAumento = $conPropuesta['diasUtilizados'] <= $antesDeEditar['diasUtilizados'];
            }
        }

        return [
            'saldoVacaciones' => $saldo,
            'diasProgramados' => $sinPropuesta['diasUtilizados'] - $saldo['diasUtilizados'],
            'diasDisponiblesParaProgramar' => (string) $disponibles,
            'diasSolicitados' => $solicitados,
            'diasADescontar' => $descontar,
            'diasRestantes' => $descontar === null ? null : (string) $disponibles->sub($descontar, 4),
            'tieneSaldoSuficiente' => $descontar === null ? null : ($tieneEdicionSinAumento || $disponibles >= new Number($descontar)),
        ];
    }
}
