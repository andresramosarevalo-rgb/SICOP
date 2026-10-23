<?php

namespace App\Http\Requests\Pos;

use App\Concerns\ReglasPago;
use App\Enums\CodigoInfraccion;
use App\Enums\FormaPago;
use App\Enums\Referido;
use App\Enums\TipoDocumento;
use App\Models\Curso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCursoRequest extends FormRequest
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
            'numero_certificado' => ['required', 'string', 'max:50'],
            'numero_comparendo' => ['required', 'string', 'max:50'],
            'secretaria_transito' => ['required', 'string', 'max:150'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'referido' => ['nullable', Rule::enum(Referido::class)],
            'codigo_infraccion' => ['required', Rule::enum(CodigoInfraccion::class)],
            'valor_curso' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'porcentaje_descuento' => ['required', Rule::in(Curso::PORCENTAJES_DESCUENTO)],
            ...$this->reglasPago(FormaPago::cases()),
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validarSumaMixta($validator, 'valor_curso')];
    }

    /**
     * Datos del curso listos para guardar, con los valores por medio de pago resueltos.
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        return $this->datosConPago('valor_curso', FormaPago::cases());
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
            'hora_fin.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'valor_curso.gt' => 'El valor debe ser mayor que cero.',
            'porcentaje_descuento.in' => 'El descuento debe ser 25 % o 50 %.',
        ];
    }
}
