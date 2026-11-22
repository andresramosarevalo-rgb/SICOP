<?php

namespace App\Actions\Nomina;

use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GuardarNovedad
{
    public function __construct(private ConsultarVacacionesNovedad $consultarVacaciones) {}

    /** @param array<string, mixed> $datos */
    public function ejecutar(array $datos, ?Novedad $editada = null): Novedad
    {
        return DB::transaction(function () use ($datos, $editada): Novedad {
            if ($editada !== null) {
                $editada = Novedad::query()->with('periodo:id,estado')->lockForUpdate()->findOrFail($editada->id);
                abort_if($editada->estaBloqueada(), 403, 'La novedad ya fue liquidada.');
            }

            $propuesta = new Novedad($datos);
            // Serializa las escrituras por empleado: dos solicitudes no pueden gastar el mismo saldo.
            $empleados = Empleado::query()
                ->whereKey(array_values(array_unique([$propuesta->empleado_id, $editada->empleado_id ?? $propuesta->empleado_id])))
                ->orderBy('id')->lockForUpdate()->get();
            $empleado = $empleados->firstWhere('id', $propuesta->empleado_id);
            abort_if($empleado === null, 404);

            if ($propuesta->tipo === TipoNovedad::Vacaciones) {
                $consulta = $this->consultarVacaciones->ejecutar($empleado, $propuesta, $editada);

                if (! $consulta['tieneSaldoSuficiente']) {
                    throw ValidationException::withMessages([
                        'fecha_fin' => 'Saldo de vacaciones insuficiente. Disponibles para programar: '
                            .$consulta['diasDisponiblesParaProgramar'].' días hábiles; este período requiere '
                            .$consulta['diasADescontar'].' días hábiles adicionales.',
                    ]);
                }
            }

            $novedad = $editada ?? new Novedad;
            $novedad->fill($datos)->save();

            return $novedad;
        }, 3);
    }
}
