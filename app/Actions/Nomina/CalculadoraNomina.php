<?php

namespace App\Actions\Nomina;

use App\Enums\ConceptoSistema;
use App\Models\Contrato;
use App\Models\ParametroNomina;
use BcMath\Number;
use RoundingMode;

/**
 * Calcula los devengos y las deducciones de ley de un empleado en un periodo.
 *
 * No consulta la base de datos: recibe el contrato y los parámetros legales ya cargados.
 * Cada valor se redondea al peso y la proporción se calcula sobre el mes comercial de 30 días.
 */
final class CalculadoraNomina
{
    private const DIAS_MES = 30;

    public function calcular(Contrato $contrato, ParametroNomina $parametros): ResultadoLiquidacion
    {
        $dias = $contrato->periodicidad_pago->dias();
        $salarioMensual = new Number($contrato->valor_salario_base);
        $lineas = [];

        $salario = $this->proporcional($salarioMensual, $dias);
        $lineas[] = $this->linea(ConceptoSistema::Salario, $salario, $dias);

        if ($salarioMensual <= new Number($parametros->valor_salario_minimo) * 2) {
            $auxilio = $this->proporcional(new Number($parametros->valor_auxilio_transporte), $dias);
            $lineas[] = $this->linea(ConceptoSistema::AuxilioTransporte, $auxilio, $dias);
        }

        $baseCotizacion = $salario;
        $lineas[] = $this->linea(ConceptoSistema::Salud, $this->porcentaje($baseCotizacion, $parametros->porcentaje_salud_empleado));
        $lineas[] = $this->linea(ConceptoSistema::Pension, $this->porcentaje($baseCotizacion, $parametros->porcentaje_pension_empleado));

        return new ResultadoLiquidacion($lineas, $dias);
    }

    /**
     * Parte de un valor mensual que corresponde a los días indicados.
     */
    private function proporcional(Number $valorMensual, int $dias): Number
    {
        return $this->redondear($valorMensual * $dias / self::DIAS_MES);
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
        return new LineaLiquidacion($concepto->value, $concepto->tipo(), $concepto->nombre(), $cantidad, $valor);
    }
}
