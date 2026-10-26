<?php

namespace App\Http\Requests\Pos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSedeRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100', Rule::unique('sedes', 'nombre')->ignore($this->route('sede'))],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:200'],
            'valor_base_caja' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'],
            'es_activa' => ['sometimes', 'boolean'],
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
            'valor_base_caja.min' => 'El valor no puede ser negativo.',
            'nombre.unique' => 'Ya existe una sede con ese nombre.',
        ];
    }
}
