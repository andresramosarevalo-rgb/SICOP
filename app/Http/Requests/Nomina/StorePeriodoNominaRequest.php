<?php

namespace App\Http\Requests\Nomina;

use App\Enums\PeriodicidadPago;
use App\Models\PeriodoNomina;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePeriodoNominaRequest extends FormRequest
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
        return [
            'periodicidad_pago' => ['required', Rule::enum(PeriodicidadPago::class)],
            'fecha_inicio' => [
                'required', 'date',
                Rule::unique('periodos_nomina', 'fecha_inicio')->where('periodicidad_pago', $this->input('periodicidad_pago')),
            ],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ];
    }

    /**
     * Impide que el periodo se cruce con otro de la misma periodicidad: un empleado cobraría dos
     * veces los días en común.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $seCruza = PeriodoNomina::query()
                ->where('periodicidad_pago', $this->input('periodicidad_pago'))
                ->whereDate('fecha_inicio', '<=', $this->input('fecha_fin'))
                ->whereDate('fecha_fin', '>=', $this->input('fecha_inicio'))
                ->exists();

            if ($seCruza) {
                $validator->errors()->add('fecha_inicio', 'El periodo se cruza con otro de la misma periodicidad.');
            }
        }];
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
            'fecha_inicio.unique' => 'Ya existe un periodo con esa periodicidad y fecha de inicio.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
