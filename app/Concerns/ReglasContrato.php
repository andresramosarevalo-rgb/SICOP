<?php

namespace App\Concerns;

use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Models\Contrato;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación de un contrato, compartidas por el registro de contratos y por el registro
 * de un empleado junto con su contrato.
 *
 * @phpstan-require-extends FormRequest
 */
trait ReglasContrato
{
    /**
     * Reglas del contrato. Si el empleado tiene un contrato vigente, el nuevo debe iniciar después de él.
     *
     * @param  string  $prefijo  Prefijo de los campos, por ejemplo "contrato." cuando el contrato llega anidado.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function reglasContrato(?Contrato $vigente = null, string $prefijo = ''): array
    {
        return [
            "{$prefijo}tipo_contrato" => ['required', Rule::enum(TipoContrato::class)],
            "{$prefijo}periodicidad_pago" => ['required', Rule::enum(PeriodicidadPago::class)],
            "{$prefijo}fecha_inicio" => ['required', 'date', ...($vigente ? ['after:'.$vigente->fecha_inicio->toDateString()] : [])],
            "{$prefijo}fecha_fin" => [
                Rule::requiredIf($this->input("{$prefijo}tipo_contrato") === TipoContrato::TerminoFijo->value),
                'nullable', 'date', "after:{$prefijo}fecha_inicio",
            ],
            "{$prefijo}valor_salario_base" => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
        ];
    }

    /**
     * Mensajes de error del contrato.
     *
     * @return array<string, string>
     */
    protected function mensajesContrato(string $prefijo = ''): array
    {
        return [
            "{$prefijo}fecha_inicio.after" => 'Debe ser posterior al inicio del contrato vigente.',
            "{$prefijo}fecha_fin.required" => 'Un contrato a término fijo necesita fecha de fin.',
            "{$prefijo}fecha_fin.after" => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            "{$prefijo}valor_salario_base.gt" => 'El salario debe ser mayor que cero.',
        ];
    }
}
