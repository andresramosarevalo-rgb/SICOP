<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConsultarVacacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-nomina');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'required_with:fecha_fin', 'date'],
            'fecha_fin' => ['nullable', 'required_with:fecha_inicio', 'date', 'after_or_equal:fecha_inicio'],
            'novedad_id' => ['nullable', 'integer'],
        ];
    }
}
