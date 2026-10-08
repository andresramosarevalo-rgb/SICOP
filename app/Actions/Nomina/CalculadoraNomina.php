<?php

namespace App\Actions\Nomina;

use App\Enums\ConceptoSistema;
use App\Enums\FormaCalculo;
use App\Enums\TipoConcepto;
use App\Enums\TipoNovedad;
use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\ParametroNomina;
use App\Models\PeriodoNomina;
use BcMath\Number;
use RoundingMode;

/**
 * Calcula los devengos y las deducciones de ley de un empleado en un periodo.
 *
 * No consulta la base de datos: recibe el contrato, los parámetros, el periodo, las novedades y
 * los conceptos asignados ya cargados. Cada valor se redondea al peso y la proporción se calcula
 * sobre el mes comercial de 30 días. Las faltas, incapacidades y vacaciones se restan de los días
 * de salario; las novedades por días solo cuentan los días que caen dentro del periodo.
 */
final class CalculadoraNomina
{
    private const DIAS_MES = 30;

    /**
     * @param  array<int, Novedad>  $novedades
     * @param  array<int, AsignacionConcepto>  $asignaciones
     */
    public function calcular(
        Contrato $contrato,
        ParametroNomina $parametros,
        PeriodoNomina $periodo,
        array $novedades = [],
        array $asignaciones = [],
    ): ResultadoLiquidacion {
        $dias = $contrato->periodicidad_pago->dias();
        $salarioMensual = new Number($contrato->valor_salario_base);
        $salarioDiario = $salarioMensual / self::DIAS_MES;
        $valorHora = $salarioMensual / $parametros->horas_mensuales;

        $diasFalta = $this->diasDe($novedades, TipoNovedad::Falta, $periodo);
        $diasIncapacidad = $this->diasDe($novedades, TipoNovedad::Incapacidad, $periodo);
        $diasVacaciones = $this->diasDe($novedades, TipoNovedad::Vacaciones, $periodo);
        $diasTrabajados = max(0, $dias - $diasFalta - $diasIncapacidad - $diasVacaciones);

        $salario = $this->redondear($salarioDiario * $diasTrabajados);
        $lineas = [$this->linea(ConceptoSistema::Salario, $salario, $diasTrabajados)];

        if ($salarioMensual <= new Number($parametros->valor_salario_minimo) * 2 && $diasTrabajados > 0) {
            $auxilio = $this->redondear(new Number($parametros->valor_auxilio_transporte) * $diasTrabajados / self::DIAS_MES);
            $lineas[] = $this->linea(ConceptoSistema::AuxilioTransporte, $auxilio, $diasTrabajados);
        }

        // Las horas extra pagan la hora completa más el recargo; los recargos pagan solo el recargo.
        $horasConRecargo = [
            [TipoNovedad::HoraExtraDiurna, ConceptoSistema::HoraExtraDiurna, new Number($parametros->porcentaje_recargo_hora_extra_diurna) + 100],
            [TipoNovedad::HoraExtraNocturna, ConceptoSistema::HoraExtraNocturna, new Number($parametros->porcentaje_recargo_hora_extra_nocturna) + 100],
            [TipoNovedad::RecargoNocturno, ConceptoSistema::RecargoNocturno, new Number($parametros->porcentaje_recargo_nocturno)],
            [TipoNovedad::DominicalFestivo, ConceptoSistema::RecargoDominicalFestivo, new Number($parametros->porcentaje_recargo_dominical_festivo)],
        ];

        foreach ($horasConRecargo as [$tipo, $concepto, $porcentaje]) {
            $horas = $this->cantidadDe($novedades, $tipo, $periodo);

            if ($horas > 0) {
                $lineas[] = $this->linea($concepto, $this->redondear($valorHora * $horas * $porcentaje / 100), $horas);
            }
        }

        if ($diasIncapacidad > 0) {
            $diarioIncapacidad = $salarioDiario * new Number($parametros->porcentaje_incapacidad) / 100;
            $minimoDiario = new Number($parametros->valor_salario_minimo) / self::DIAS_MES;
            $diario = $diarioIncapacidad < $minimoDiario ? $minimoDiario : $diarioIncapacidad;
            $lineas[] = $this->linea(ConceptoSistema::Incapacidad, $this->redondear($diario * $diasIncapacidad), $diasIncapacidad);
        }

        if ($diasVacaciones > 0) {
            $lineas[] = $this->linea(ConceptoSistema::Vacaciones, $this->redondear($salarioDiario * $diasVacaciones), $diasVacaciones);
        }

        $minutosRetardo = $this->cantidadDe($novedades, TipoNovedad::Retardo, $periodo);

        if ($minutosRetardo > 0) {
            $lineas[] = $this->linea(ConceptoSistema::DescuentoRetardo, $this->redondear($valorHora * $minutosRetardo / 60), $minutosRetardo);
        }

        foreach ($asignaciones as $asignacion) {
            if ($this->estaVigente($asignacion, $periodo)) {
                $lineas[] = $this->lineaDeConcepto($asignacion->concepto, $this->valorAsignado($asignacion, $salario));
            }
        }

        foreach ($novedades as $novedad) {
            if ($novedad->tipo === TipoNovedad::ConceptoEventual && $novedad->concepto !== null
                && $novedad->fecha_inicio->between($periodo->fecha_inicio, $periodo->fecha_fin)) {
                $lineas[] = $this->lineaDeConcepto($novedad->concepto, $this->redondear(new Number($novedad->valor_eventual ?? '0')));
            }
        }

        $baseCotizacion = new Number('0');

        foreach ($lineas as $linea) {
            if ($linea->esConstitutivoSalario) {
                $baseCotizacion += $linea->valor;
            }
        }

        $lineas[] = $this->linea(ConceptoSistema::Salud, $this->porcentaje($baseCotizacion, $parametros->porcentaje_salud_empleado));
        $lineas[] = $this->linea(ConceptoSistema::Pension, $this->porcentaje($baseCotizacion, $parametros->porcentaje_pension_empleado));

        return new ResultadoLiquidacion($lineas, $diasTrabajados);
    }

    /**
     * Días de las novedades de un tipo que caen dentro del periodo.
     *
     * @param  array<int, Novedad>  $novedades
     */
    private function diasDe(array $novedades, TipoNovedad $tipo, PeriodoNomina $periodo): int
    {
        $dias = 0;

        foreach ($novedades as $novedad) {
            if ($novedad->tipo === $tipo) {
                $desde = $novedad->fecha_inicio->max($periodo->fecha_inicio);
                $hasta = ($novedad->fecha_fin ?? $novedad->fecha_inicio)->min($periodo->fecha_fin);
                $dias += max(0, (int) $desde->diffInDays($hasta, false) + 1);
            }
        }

        return $dias;
    }

    /**
     * Suma de las horas o minutos de las novedades de un tipo registradas dentro del periodo.
     *
     * @param  array<int, Novedad>  $novedades
     */
    private function cantidadDe(array $novedades, TipoNovedad $tipo, PeriodoNomina $periodo): int
    {
        $cantidad = 0;

        foreach ($novedades as $novedad) {
            if ($novedad->tipo === $tipo && $novedad->fecha_inicio->between($periodo->fecha_inicio, $periodo->fecha_fin)) {
                $cantidad += $novedad->cantidad ?? 0;
            }
        }

        return $cantidad;
    }

    /**
     * Indica si la asignación está vigente en algún día del periodo.
     */
    private function estaVigente(AsignacionConcepto $asignacion, PeriodoNomina $periodo): bool
    {
        return $asignacion->fecha_inicio->lte($periodo->fecha_fin)
            && ($asignacion->fecha_fin === null || $asignacion->fecha_fin->gte($periodo->fecha_inicio));
    }

    /**
     * Valor de un concepto asignado: un porcentaje del salario del periodo, o el valor asignado
     * (o en su defecto el del concepto) cuando es de valor fijo.
     */
    private function valorAsignado(AsignacionConcepto $asignacion, Number $salario): Number
    {
        $concepto = $asignacion->concepto;

        if ($concepto->forma_calculo === FormaCalculo::Porcentaje) {
            return $this->porcentaje($salario, $concepto->porcentaje_base ?? '0');
        }

        return $this->redondear(new Number($asignacion->valor_asignado ?? $concepto->valor_base ?? '0'));
    }

    private function lineaDeConcepto(ConceptoNomina $concepto, Number $valor): LineaLiquidacion
    {
        $esConstitutivo = $concepto->tipo === TipoConcepto::Devengo && $concepto->es_constitutivo_salario;

        return new LineaLiquidacion($concepto->codigo, $concepto->tipo, $concepto->nombre, null, $valor, $esConstitutivo);
    }

    /**
     * Porcentaje de una base, redondeado al peso.
     *
     * @param  numeric-string  $porcentaje
     */
    private function porcentaje(Number $base, string $porcentaje): Number
    {
        return $this->redondear($base * new Number($porcentaje) / 100);
    }

    private function redondear(Number $valor): Number
    {
        return $valor->round(0, RoundingMode::HalfAwayFromZero);
    }

    private function linea(ConceptoSistema $concepto, Number $valor, ?int $cantidad = null): LineaLiquidacion
    {
        return new LineaLiquidacion(
            $concepto->value, $concepto->tipo(), $concepto->nombre(), $cantidad, $valor, $concepto->esConstitutivoSalario(),
        );
    }
}
