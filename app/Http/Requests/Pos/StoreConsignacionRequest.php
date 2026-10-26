<?php

namespace App\Http\Requests\Pos;

use App\Models\CierreCaja;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreConsignacionRequest extends FormRequest
{
    /**
     * Solo el gerente consigna, y solo un día cerrado que aún no tiene su consignación.
     */
    public function authorize(): bool
    {
        /** @var CierreCaja $cierre */
        $cierre = $this->route('cierre');

        return $this->user()->can('gestionar-pos') && $cierre->estaCerrado() && ! $cierre->consignacion()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var CierreCaja $cierre */
        $cierre = $this->route('cierre');

        return [
            'fecha_consignacion' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$cierre->fecha->toDateString(), 'before_or_equal:today'],
            'valor_consignado' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'referencia' => ['required', 'string', 'max:100'],
            'soporte' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var CierreCaja $cierre */
            $cierre = $this->route('cierre');

            if ($validator->errors()->isEmpty() && round((float) $this->input('valor_consignado'), 2) > (float) $cierre->valor_a_consignar) {
                $validator->errors()->add('valor_consignado', 'No puede superar lo que debía consignar la sede.');
            }
        }];
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
            'fecha_consignacion.after_or_equal' => 'La consignación no puede ser anterior al día cerrado.',
            'fecha_consignacion.before_or_equal' => 'La fecha no puede ser futura.',
            'valor_consignado.gt' => 'El valor debe ser mayor que cero.',
            'soporte.required' => 'Adjunte el comprobante de la consignación.',
            'soporte.mimes' => 'El comprobante debe ser una imagen JPG o PNG, o un PDF.',
            'soporte.max' => 'El comprobante no puede pesar más de 5 MB.',
        ];
    }
}
