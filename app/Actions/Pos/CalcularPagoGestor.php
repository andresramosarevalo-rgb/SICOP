<?php

namespace App\Actions\Pos;

use App\Enums\CodigoInfraccion;
use App\Enums\Referido;
use App\Models\TarifaGestor;

class CalcularPagoGestor
{
    /**
     * Valor que se paga al gestor por un curso: cero si el referido no es Gestor; si lo es, el
     * ajuste de la sede para el código de infracción o, si no lo tiene, la tarifa base.
     */
    public function ejecutar(int $sedeId, CodigoInfraccion $codigo, ?Referido $referido): string
    {
        if ($referido !== Referido::Gestor) {
            return '0.00';
        }

        $tarifa = TarifaGestor::query()
            ->where('codigo_infraccion', $codigo)
            ->where(fn ($query) => $query->where('sede_id', $sedeId)->orWhereNull('sede_id'))
            ->orderByRaw('sede_id is null')
            ->first();

        return $tarifa?->valor_pago_gestor ?? '0.00';
    }
}
