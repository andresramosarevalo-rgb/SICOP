<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsignacionConceptoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-nomina');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Solo se asignan conceptos activos que no son de sistema; los de sistema los calcula la ley.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'concepto_nomina_id' => ['required', Rule::exists('conceptos_nomina', 'id')->where(fn (Builder $query) => $query->where('es_activo', true)->where('es_sistema', false))],
            'valor_asignado' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'concepto_nomina_id.exists' => 'Seleccione un concepto activo que no sea de sistema.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ];
    }
}
