<?php

namespace App\Http\Requests\Nomina;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaldoInicialVacacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-nomina');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var string $zonaHoraria */
        $zonaHoraria = config('vacaciones.zona_horaria', 'America/Bogota');
        $hoy = CarbonImmutable::now($zonaHoraria)->toDateString();

        return [
            'dias_vacaciones_historicos' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'fecha_corte_vacaciones' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$hoy],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'dias_vacaciones_historicos.required' => 'Informe el total de días hábiles disfrutados hasta el corte.',
            'dias_vacaciones_historicos.integer' => 'Los días disfrutados deben ser un número entero.',
            'dias_vacaciones_historicos.min' => 'Los días disfrutados no pueden ser negativos.',
            'dias_vacaciones_historicos.max' => 'El total de días disfrutados supera el valor permitido.',
            'fecha_corte_vacaciones.required' => 'Indique la fecha de corte del historial de vacaciones.',
            'fecha_corte_vacaciones.date_format' => 'La fecha de corte debe ser una fecha válida en formato año-mes-día.',
            'fecha_corte_vacaciones.before_or_equal' => 'La fecha de corte no puede ser posterior a hoy.',
        ];
    }
}
