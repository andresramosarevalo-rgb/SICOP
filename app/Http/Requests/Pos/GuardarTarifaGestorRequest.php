<?php

namespace App\Http\Requests\Pos;

use App\Enums\CodigoInfraccion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTarifaGestorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-pos');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo_infraccion' => ['required', Rule::enum(CodigoInfraccion::class)],
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')],
            'valor_pago_gestor' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'],
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
            'valor_pago_gestor.min' => 'El valor no puede ser negativo.',
            'sede_id.exists' => 'Seleccione una sede registrada.',
        ];
    }
}
