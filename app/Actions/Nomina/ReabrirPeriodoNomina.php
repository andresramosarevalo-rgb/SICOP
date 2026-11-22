<?php

namespace App\Actions\Nomina;

use App\Enums\EstadoPeriodo;
use App\Models\PeriodoNomina;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reabre un periodo cerrado para corregir sus novedades y volver a liquidarlo. Los recibos vigentes
 * siguen siéndolo hasta que se vuelva a liquidar; el cambio de estado queda en la auditoría del periodo.
 */
final class ReabrirPeriodoNomina
{
    /**
     * @throws ValidationException si el periodo no está cerrado.
     */
    public function ejecutar(PeriodoNomina $periodo, User $usuario): void
    {
        DB::transaction(function () use ($periodo, $usuario): void {
            $actual = PeriodoNomina::query()->lockForUpdate()->findOrFail($periodo->id);

            if (! $actual->estaCerrado()) {
                throw ValidationException::withMessages(['periodo' => 'Solo se puede reabrir un periodo cerrado.']);
            }

            $actual->forceFill([
                'estado' => EstadoPeriodo::Reabierto,
                'reabierto_por' => $usuario->id,
                'fecha_reapertura' => now(),
                'fecha_cierre' => null,
            ])->save();
        });
    }
}
