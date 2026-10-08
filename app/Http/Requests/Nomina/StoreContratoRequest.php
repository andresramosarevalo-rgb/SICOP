<?php

namespace App\Http\Requests\Nomina;

use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Models\Empleado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratoRequest extends FormRequest
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
     * Si el empleado tiene un contrato vigente, el nuevo debe iniciar después de él.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Empleado $empleado */
        $empleado = $this->route('empleado');
        $vigente = $empleado->contratoVigente;

        return [
            'tipo_contrato' => ['required', Rule::enum(TipoContrato::class)],
            'periodicidad_pago' => ['required', Rule::enum(PeriodicidadPago::class)],
            'fecha_inicio' => ['required', 'date', ...($vigente ? ['after:'.$vigente->fecha_inicio->toDateString()] : [])],
            'fecha_fin' => [
                Rule::requiredIf($this->input('tipo_contrato') === TipoContrato::TerminoFijo->value),
                'nullable', 'date', 'after:fecha_inicio',
            ],
            'valor_salario_base' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
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
            'fecha_inicio.after' => 'Debe ser posterior al inicio del contrato vigente.',
            'fecha_fin.required' => 'Un contrato a término fijo necesita fecha de fin.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'valor_salario_base.gt' => 'El salario debe ser mayor que cero.',
        ];
    }
}
