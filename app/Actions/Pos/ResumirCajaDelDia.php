<?php

namespace App\Actions\Pos;

use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use Illuminate\Support\Carbon;

class ResumirCajaDelDia
{
    /**
     * Cifras que el sistema calcula de las ventas de una sede en un día: lo vendido por medio de
     * pago y lo que se paga a los gestores. Los gastos y el arqueo los aporta quien cierra.
     *
     * @return array{valor_base_caja: string, valor_efectivo_ventas: string, valor_ventas_datafono: string, valor_ventas_qr: string, valor_pago_gestores: string, cantidad_cursos: int, cantidad_soats: int}
     */
    public function ejecutar(Sede $sede, Carbon|string $fecha): array
    {
        $cursos = Curso::query()
            ->where('sede_id', $sede->id)
            ->whereDate('fecha', $fecha)
            ->selectRaw('count(*) as cantidad, coalesce(sum(valor_efectivo), 0) as efectivo, coalesce(sum(valor_datafono), 0) as datafono, coalesce(sum(valor_qr), 0) as qr, coalesce(sum(valor_pago_gestor), 0) as gestores')
            ->first();

        $soats = Soat::query()
            ->where('sede_id', $sede->id)
            ->whereDate('fecha', $fecha)
            ->selectRaw('count(*) as cantidad, coalesce(sum(valor_efectivo), 0) as efectivo, coalesce(sum(valor_qr), 0) as qr')
            ->first();

        return [
            'valor_base_caja' => $this->pesos($sede->valor_base_caja),
            'valor_efectivo_ventas' => $this->pesos($cursos->efectivo + $soats->efectivo),
            'valor_ventas_datafono' => $this->pesos($cursos->datafono),
            'valor_ventas_qr' => $this->pesos($cursos->qr + $soats->qr),
            'valor_pago_gestores' => $this->pesos($cursos->gestores),
            'cantidad_cursos' => (int) $cursos->cantidad,
            'cantidad_soats' => (int) $soats->cantidad,
        ];
    }

    /**
     * Valor con dos decimales para evitar errores de redondeo al sumar.
     */
    private function pesos(float|int|string $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
