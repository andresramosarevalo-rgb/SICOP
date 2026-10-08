<?php

namespace App\Http\Requests\Nomina;

use App\Enums\PeriodicidadPago;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
