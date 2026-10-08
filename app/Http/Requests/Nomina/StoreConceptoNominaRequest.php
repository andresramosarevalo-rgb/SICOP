<?php

namespace App\Http\Requests\Nomina;

use App\Enums\FormaCalculo;
use App\Enums\TipoConcepto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConceptoNominaRequest extends FormRequest
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
     * Los conceptos que registra el usuario son de valor fijo o porcentaje; los de sistema los carga el seeder.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'regex:/^[A-Z0-9_]+$/', 'max:20', Rule::unique('conceptos_nomina', 'codigo')->ignore($this->route('concepto'))],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::enum(TipoConcepto::class)],
            'forma_calculo' => ['required', Rule::in([FormaCalculo::ValorFijo->value, FormaCalculo::Porcentaje->value])],
            'valor_base' => ['nullable', 'required_if:forma_calculo,valor_fijo', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'porcentaje_base' => ['nullable', 'required_if:forma_calculo,porcentaje', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'es_constitutivo_salario' => ['required', 'boolean'],
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
            'codigo.regex' => 'Use solo mayúsculas, números y guion bajo.',
            'codigo.unique' => 'Ya existe un concepto con ese código.',
            'valor_base.required_if' => 'Indique el valor del concepto.',
            'porcentaje_base.required_if' => 'Indique el porcentaje del concepto.',
        ];
    }
}
