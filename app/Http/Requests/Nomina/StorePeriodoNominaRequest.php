<?php

namespace App\Http\Requests\Nomina;

use App\Enums\PeriodicidadPago;
use App\Models\PeriodoNomina;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
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
     * Quien no es super administrador crea los periodos siempre de su propia sede.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->esSuperAdmin()) {
            $this->merge(['sede_id' => $this->user()->sede_id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Sin `sede_id` (solo el super administrador) el periodo liquida a todas las sedes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')->where('es_activa', true)],
            'periodicidad_pago' => ['required', Rule::enum(PeriodicidadPago::class)],
            'fecha_inicio' => [
                'required', 'date',
                Rule::unique('periodos_nomina', 'fecha_inicio')
                    ->where('periodicidad_pago', $this->input('periodicidad_pago'))
                    ->where(fn (Builder $query) => $this->filled('sede_id')
                        ? $query->where('sede_id', $this->input('sede_id'))
                        : $query->whereNull('sede_id')),
            ],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ];
    }

    /**
     * Impide que el periodo se cruce con otro de la misma periodicidad que alcance a los mismos
     * empleados (de la misma sede o de todas las sedes): un empleado cobraría dos veces los días en común.
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
                ->when($this->filled('sede_id'), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('sede_id')->orWhere('sede_id', $this->input('sede_id'))))
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
            'sede_id.exists' => 'Seleccione una sede activa.',
            'fecha_inicio.unique' => 'Ya existe un periodo con esa periodicidad y fecha de inicio.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
