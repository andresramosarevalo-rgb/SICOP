<?php

namespace App\Http\Requests\Pos;

use App\Concerns\ReglasPago;
use App\Enums\FormaPago;
use App\Enums\TipoDocumento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSoatRequest extends FormRequest
{
    use ReglasPago;

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
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'numero_documento' => ['required', 'alpha_num', 'max:20'],
            'nombre_cliente' => ['required', 'string', 'max:200'],
            'celular' => ['required', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:255'],
            'placa' => ['required', 'alpha_num', 'max:10'],
            'valor_soat' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            ...$this->reglasPago(FormaPago::PARA_SOAT),
        ];
    }

    /**
     * Prepare the data for validation: la placa se guarda en mayúsculas y sin espacios ni guiones.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('placa'))) {
            $this->merge(['placa' => Str::upper(str_replace([' ', '-'], '', $this->input('placa')))]);
        }
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validarSumaMixta($validator, 'valor_soat', FormaPago::PARA_SOAT)];
    }

    /**
     * Datos del SOAT listos para guardar, con los valores por medio de pago resueltos.
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        return $this->datosConPago('valor_soat', FormaPago::PARA_SOAT);
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
            'correo.email' => 'Escriba un correo válido.',
            'placa.alpha_num' => 'Use solo letras y números.',
            'valor_soat.gt' => 'El valor debe ser mayor que cero.',
        ];
    }
}
