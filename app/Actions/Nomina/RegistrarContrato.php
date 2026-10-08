<?php

namespace App\Actions\Nomina;

use App\Models\Contrato;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class RegistrarContrato
{
    /**
     * Registra un contrato vigente para el empleado. Si ya tenía uno vigente, lo cierra
     * el día anterior al inicio del nuevo (o en su propia fecha de fin, si es anterior).
     *
     * @param  array<string, mixed>  $datos  Datos validados por StoreContratoRequest.
     */
    public function ejecutar(Empleado $empleado, array $datos): Contrato
    {
        return DB::transaction(function () use ($empleado, $datos): Contrato {
            $nuevo = $empleado->contratos()->make([...$datos, 'es_vigente' => true]);
            $anterior = $empleado->contratoVigente()->lockForUpdate()->first();

            if ($anterior !== null) {
                $cierre = $nuevo->fecha_inicio->subDay();

                $anterior->update([
                    'es_vigente' => false,
                    'fecha_fin' => $anterior->fecha_fin?->lessThan($cierre) ? $anterior->fecha_fin : $cierre,
                ]);
            }

            $nuevo->save();

            return $nuevo;
        });
    }
}
