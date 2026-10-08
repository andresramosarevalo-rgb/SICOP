<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParametroNominaRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $valor = ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'];
        $porcentaje = ['required', 'numeric', 'min:0', 'max:999', 'decimal:0,2'];

        return [
            'anio' => ['required', 'integer', 'between:2020,2100', Rule::unique('parametros_nomina', 'anio')->ignore($this->route('parametro'))],
            'valor_salario_minimo' => $valor,
            'valor_auxilio_transporte' => $valor,
            'valor_uvt' => $valor,
            'porcentaje_salud_empleado' => $porcentaje,
            'porcentaje_pension_empleado' => $porcentaje,
            'horas_mensuales' => ['required', 'integer', 'between:1,300'],
            'porcentaje_recargo_hora_extra_diurna' => $porcentaje,
            'porcentaje_recargo_hora_extra_nocturna' => $porcentaje,
            'porcentaje_recargo_nocturno' => $porcentaje,
            'porcentaje_recargo_dominical_festivo' => $porcentaje,
            'porcentaje_incapacidad' => $porcentaje,
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
            'anio.unique' => 'Ya existen parámetros para ese año.',
        ];
    }
}
