<?php

namespace App\Http\Requests\Pos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CerrarCajaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('operar-sede', $this->route('sede'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'valor_efectivo_contado' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'],
            'valor_reportado_datafono' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'],
            'valor_reportado_qr' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'],
            'justificacion' => ['nullable', 'string', 'max:1000'],
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
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'min' => 'El valor no puede ser negativo.',
        ];
    }
}
