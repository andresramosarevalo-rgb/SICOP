<?php

namespace App\Http\Requests\Nomina;

use App\Concerns\ReglasContrato;
use App\Enums\TipoCuentaBancaria;
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
     * Quien no es super administrador registra y edita empleados siempre en su propia sede.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->esSuperAdmin()) {
            $this->merge(['sede_id' => $this->user()->sede_id]);
        }
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
            'sede_id' => ['required', Rule::exists('sedes', 'id')->where('es_activa', true)],
            'cargo' => ['required', 'string', 'max:100'],
            'banco' => ['required', 'string', 'max:255'],
            'numero_cuenta_bancaria' => ['required', 'string', 'max:255'],
            'tipo_cuenta_bancaria' => ['required', Rule::enum(TipoCuentaBancaria::class)],
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
            'banco.required' => 'Ingrese el banco.',
            'banco.string' => 'El banco debe ser un texto.',
            'banco.max' => 'El banco no debe superar los 255 caracteres.',
            'numero_cuenta_bancaria.required' => 'Ingrese el número de cuenta bancaria.',
            'numero_cuenta_bancaria.string' => 'El número de cuenta bancaria debe ser un texto.',
            'numero_cuenta_bancaria.max' => 'El número de cuenta bancaria no debe superar los 255 caracteres.',
            'tipo_cuenta_bancaria.required' => 'Seleccione el tipo de cuenta bancaria.',
            'tipo_cuenta_bancaria.enum' => 'Seleccione un tipo de cuenta bancaria válido.',
            'numero_documento.unique' => 'Ya existe un empleado con ese número de documento.',
            'area_id.exists' => 'Seleccione un área activa.',
            'sede_id.exists' => 'Seleccione una sede activa.',
            ...$this->mensajesContrato('contrato.'),
        ];
    }
}
