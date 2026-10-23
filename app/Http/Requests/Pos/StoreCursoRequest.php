<?php

namespace App\Http\Requests\Pos;

use App\Enums\TipoDocumento;
use App\Models\Curso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCursoRequest extends FormRequest
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
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'numero_documento' => ['required', 'alpha_num', 'max:20'],
            'nombre_cliente' => ['required', 'string', 'max:200'],
            'tipo_comparendo' => ['required', 'string', 'max:100'],
            'valor_curso' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'porcentaje_descuento' => ['required', Rule::in(Curso::PORCENTAJES_DESCUENTO)],
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
            'numero_documento.alpha_num' => 'Use solo letras y números, sin puntos ni espacios.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'valor_curso.gt' => 'El valor debe ser mayor que cero.',
            'porcentaje_descuento.in' => 'El descuento debe ser 25 % o 50 %.',
        ];
    }
}
