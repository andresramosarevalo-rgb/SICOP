<?php

namespace App\Http\Requests\Pos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGastoCajaRequest extends FormRequest
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
            'concepto' => ['required', 'string', 'max:200'],
            'valor_gasto' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'soporte' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
            'valor_gasto.gt' => 'El valor debe ser mayor que cero.',
            'soporte.required' => 'Adjunte el soporte del gasto.',
            'soporte.mimes' => 'El soporte debe ser una imagen JPG o PNG, o un PDF.',
            'soporte.max' => 'El soporte no puede pesar más de 5 MB.',
        ];
    }
}
