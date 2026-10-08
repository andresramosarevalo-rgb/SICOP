<?php

namespace App\Actions\Nomina;

use App\Enums\ConceptoSistema;
use App\Enums\EstadoPeriodo;
use App\Models\ConceptoNomina;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\ParametroNomina;
use App\Models\PeriodoNomina;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Liquida un periodo: genera un recibo por cada empleado activo con contrato vigente de la misma
 * periodicidad, con sus novedades. Si el periodo ya estaba liquidado, reemplaza
 * los recibos anteriores. Las novedades que entran en la liquidación quedan asociadas al periodo.
 */
final class LiquidarPeriodoNomina
{
    public function __construct(private CalculadoraNomina $calculadora) {}

    /**
     * @throws ValidationException si el periodo está cerrado, faltan los parámetros del año o los conceptos de sistema.
     */
    public function ejecutar(PeriodoNomina $periodo, User $usuario): void
    {
        if ($periodo->estaCerrado()) {
            throw ValidationException::withMessages(['periodo' => 'El periodo está cerrado y no se puede volver a liquidar.']);
        }

        $anio = $periodo->fecha_inicio->year;
        $parametros = ParametroNomina::query()->where('anio', $anio)->first()
            ?? throw ValidationException::withMessages(['periodo' => "No hay parámetros legales registrados para {$anio}."]);

        $conceptos = ConceptoNomina::query()->pluck('id', 'codigo');

        if (! collect(ConceptoSistema::cases())->every(fn (ConceptoSistema $concepto) => $conceptos->has($concepto->value))) {
            throw ValidationException::withMessages(['periodo' => 'Faltan conceptos de sistema. Ejecute el seeder de nómina.']);
        }

        DB::transaction(function () use ($periodo, $usuario, $parametros, $conceptos): void {
            $periodo->recibos()->delete();
            $periodo->novedades()->update(['periodo_nomina_id' => null]);

            $contratos = Contrato::query()
                ->where('es_vigente', true)
                ->where('periodicidad_pago', $periodo->periodicidad_pago)
                ->where('fecha_inicio', '<=', $periodo->fecha_fin)
                ->whereHas('empleado', fn (Builder $query) => $query->where('es_activo', true))
                ->with([
                    'empleado.novedades' => fn (Relation $query) => $query
                        ->where('fecha_inicio', '<=', $periodo->fecha_fin)
                        ->where(fn (Builder $query) => $query->where('fecha_fin', '>=', $periodo->fecha_inicio)
                            ->orWhere(fn (Builder $query) => $query->whereNull('fecha_fin')->where('fecha_inicio', '>=', $periodo->fecha_inicio))),
                ])
                ->get();

            foreach ($contratos as $contrato) {
                $novedades = $contrato->empleado->novedades;
                $resultado = $this->calculadora->calcular(
                    $contrato, $parametros, $periodo, $novedades->all(),
                );

                $recibo = $periodo->recibos()->create([
                    'empleado_id' => $contrato->empleado_id,
                    'contrato_id' => $contrato->id,
                    'valor_salario_base' => $contrato->valor_salario_base,
                    'dias_liquidados' => $resultado->diasLiquidados,
                    'valor_total_devengado' => (string) $resultado->totalDevengado(),
                    'valor_total_deducciones' => (string) $resultado->totalDeducciones(),
                    'valor_neto' => (string) $resultado->neto(),
                ]);

                $recibo->detalles()->createMany(array_map(fn (LineaLiquidacion $linea) => [
                    'concepto_nomina_id' => $conceptos[$linea->codigoConcepto],
                    'tipo' => $linea->tipo,
                    'descripcion' => $linea->descripcion,
                    'cantidad' => $linea->cantidad,
                    'valor_concepto' => (string) $linea->valor,
                ], $resultado->lineas));

                Novedad::query()->whereKey($novedades->modelKeys())->update(['periodo_nomina_id' => $periodo->id]);
            }

            $periodo->forceFill([
                'estado' => EstadoPeriodo::Liquidado,
                'liquidado_por' => $usuario->id,
                'fecha_liquidacion' => now(),
            ])->save();
        });
    }
}
