<?php

namespace App\Http\Controllers\Nomina;

use App\Actions\Nomina\ConsultarVacacionesNovedad;
use App\Enums\TipoNovedad;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\ConsultarVacacionesRequest;
use App\Models\Empleado;
use App\Models\Novedad;
use Illuminate\Http\JsonResponse;

class VacacionesNovedadController extends Controller
{
    public function __invoke(ConsultarVacacionesRequest $request, Empleado $empleado, ConsultarVacacionesNovedad $consulta): JsonResponse
    {
        $datos = $request->validated();
        $editada = isset($datos['novedad_id'])
            ? Novedad::query()->visiblesPara($request->user())->findOrFail((int) $datos['novedad_id'])
            : null;

        abort_if($editada?->estaBloqueada(), 403, 'La novedad ya fue liquidada.');

        $propuesta = isset($datos['fecha_inicio'], $datos['fecha_fin']) ? new Novedad([
            'empleado_id' => $empleado->id,
            'tipo' => TipoNovedad::Vacaciones,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
        ]) : null;

        return response()->json($consulta->ejecutar($empleado, $propuesta, $editada));
    }
}
