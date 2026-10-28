<?php

namespace App\Http\Requests\Nomina;

use App\Actions\Nomina\LimiteHorasExtra;
use App\Enums\TipoNovedad;
use App\Models\Novedad;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNovedadRequest extends FormRequest
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
     * Los datos que se exigen dependen del tipo: horas o minutos, rango de fechas, o concepto y valor.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tipo = TipoNovedad::tryFrom((string) $this->input('tipo'));

        return [
            'empleado_id' => ['required', Rule::exists('empleados', 'id')->where('es_activo', true)],
            'tipo' => ['required', Rule::enum(TipoNovedad::class)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => [
                Rule::requiredIf(in_array($tipo, [TipoNovedad::Incapacidad, TipoNovedad::Vacaciones], true)),
                Rule::prohibitedIf($tipo !== null && ! $tipo->esPorDias()),
                'nullable', 'date', 'after_or_equal:fecha_inicio',
            ],
            'cantidad' => [
                Rule::requiredIf($tipo?->unidad() !== null),
                Rule::prohibitedIf($tipo !== null && $tipo->unidad() === null),
                'nullable', 'integer', 'min:1', $tipo?->unidad() === 'minutos' ? 'max:600' : 'max:200',
            ],
            'concepto_nomina_id' => [
                Rule::requiredIf($tipo === TipoNovedad::ConceptoEventual),
                Rule::prohibitedIf($tipo !== null && $tipo !== TipoNovedad::ConceptoEventual),
                'nullable',
                Rule::exists('conceptos_nomina', 'id')->where(fn (Builder $query) => $query->where('es_activo', true)->where('es_sistema', false)),
            ],
            'valor_eventual' => [
                Rule::requiredIf($tipo === TipoNovedad::ConceptoEventual),
                Rule::prohibitedIf($tipo !== null && $tipo !== TipoNovedad::ConceptoEventual),
                'nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999',
            ],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Datos de la novedad listos para guardar: los campos que no aplican al tipo quedan en null, para
     * que al cambiar el tipo de una novedad no se conserven los valores del tipo anterior.
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        return [...array_fill_keys(['fecha_fin', 'cantidad', 'concepto_nomina_id', 'valor_eventual'], null), ...$this->validated()];
    }

    /**
     * Impide que las horas extra del empleado pasen del máximo legal (ver `LimiteHorasExtra`).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $tipo = TipoNovedad::tryFrom((string) $this->input('tipo'));

            if ($validator->errors()->isNotEmpty() || ! in_array($tipo, [TipoNovedad::HoraExtraDiurna, TipoNovedad::HoraExtraNocturna], true)) {
                return;
            }

            /** @var Novedad|null $editada */
            $editada = $this->route('novedad');

            $motivo = app(LimiteHorasExtra::class)->motivoDeRechazo(
                (int) $this->input('empleado_id'),
                CarbonImmutable::parse($this->input('fecha_inicio')),
                (int) $this->input('cantidad'),
                $editada,
            );

            if ($motivo !== null) {
                $validator->errors()->add('cantidad', $motivo);
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
            'prohibited' => 'Este dato no aplica para el tipo de novedad.',
            'empleado_id.exists' => 'Seleccione un empleado activo.',
            'cantidad.required' => 'Indique la cantidad de horas o minutos.',
            'fecha_fin.required' => 'Indique la fecha de fin.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'concepto_nomina_id.exists' => 'Seleccione un concepto activo que no sea de sistema.',
        ];
    }
}
