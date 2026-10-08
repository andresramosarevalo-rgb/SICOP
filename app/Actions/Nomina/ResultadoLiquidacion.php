<?php

namespace App\Actions\Nomina;

use App\Enums\TipoConcepto;
use BcMath\Number;

/**
 * Resultado del cálculo de nómina de un empleado en un periodo.
 */
final readonly class ResultadoLiquidacion
{
    /**
     * @param  list<LineaLiquidacion>  $lineas
     */
    public function __construct(
        public array $lineas,
        public int $diasLiquidados,
    ) {}

    /**
     * Suma de los devengos.
     */
    public function totalDevengado(): Number
    {
        return $this->sumar(TipoConcepto::Devengo);
    }

    /**
     * Suma de las deducciones.
     */
    public function totalDeducciones(): Number
    {
        return $this->sumar(TipoConcepto::Deduccion);
    }

    /**
     * Valor a pagar al empleado: devengos menos deducciones.
     */
    public function neto(): Number
    {
        return $this->totalDevengado() - $this->totalDeducciones();
    }

    /**
     * Valor de la línea de un concepto, o cero si el recibo no la tiene.
     */
    public function valorDe(string $codigoConcepto): Number
    {
        foreach ($this->lineas as $linea) {
            if ($linea->codigoConcepto === $codigoConcepto) {
                return $linea->valor;
            }
        }

        return new Number('0');
    }

    private function sumar(TipoConcepto $tipo): Number
    {
        $total = new Number('0');

        foreach ($this->lineas as $linea) {
            if ($linea->tipo === $tipo) {
                $total += $linea->valor;
            }
        }

        return $total;
    }
}
