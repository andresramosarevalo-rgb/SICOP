<?php

namespace App\Http\Requests\Nomina;

use App\Concerns\ReglasContrato;
use App\Enums\TipoDocumento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpleadoRequest extends FormRequest
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
     * El empleado se registra junto con su contrato, que llega anidado en `contrato`.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...$this->reglasEmpleado(), ...$this->reglasContrato(prefijo: 'contrato.')];
    }

    /**
     * Reglas de los datos personales del empleado.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function reglasEmpleado(): array
    {
        return [
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'numero_documento' => ['required', 'alpha_num', 'max:20', Rule::unique('empleados', 'numero_documento')],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'area_id' => ['required', Rule::exists('areas', 'id')->where('es_activa', true)],
            'cargo' => ['required', 'string', 'max:100'],
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
            'numero_documento.unique' => 'Ya existe un empleado con ese número de documento.',
            'area_id.exists' => 'Seleccione un área activa.',
            ...$this->mensajesContrato('contrato.'),
        ];
    }
}
