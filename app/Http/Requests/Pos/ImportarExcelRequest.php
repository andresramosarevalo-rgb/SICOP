<?php

namespace App\Http\Requests\Pos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarExcelRequest extends FormRequest
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
            'archivo' => ['required', 'file', 'extensions:xlsx', 'max:2048'],
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
            'archivo.required' => 'Elija el archivo de Excel.',
            'archivo.file' => 'Elija el archivo de Excel.',
            'archivo.extensions' => 'El archivo debe ser de Excel (.xlsx).',
            'archivo.max' => 'El archivo no debe superar los 2 MB.',
        ];
    }
}
