<?php

namespace App\Http\Requests\Nomina;

use App\Concerns\ReglasContrato;
use App\Models\Empleado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContratoRequest extends FormRequest
{
    use ReglasContrato;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Empleado $empleado */
        $empleado = $this->route('empleado');

        return $this->reglasContrato($empleado->contratoVigente);
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['required' => 'Este campo es obligatorio.', ...$this->mensajesContrato()];
    }
}
