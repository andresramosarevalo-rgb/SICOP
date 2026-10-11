<?php

namespace App\Http\Requests\Nomina;

use App\Models\Empleado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateEmpleadoRequest extends StoreEmpleadoRequest
{
    /**
     * Igual que el registro, pero el documento puede ser el del mismo empleado y el área su área actual aunque esté inactiva.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Empleado $empleado */
        $empleado = $this->route('empleado');

        return [
            ...parent::rules(),
            'numero_documento' => ['required', 'alpha_num', 'max:20', Rule::unique('empleados', 'numero_documento')->ignore($empleado)],
            'area_id' => ['required', Rule::exists('areas', 'id')->where(fn (Builder $query) => $query
                ->where('es_activa', true)
                ->orWhere('id', $empleado->area_id))],
        ];
    }
}
