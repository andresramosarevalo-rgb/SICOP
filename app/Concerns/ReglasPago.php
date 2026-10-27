<?php

namespace App\Concerns;

use App\Enums\FormaPago;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas de la forma de pago de una venta, compartidas por el curso y el SOAT. Si el pago es mixto
 * se piden los valores por medio y deben sumar el valor de la venta; en los demás casos el valor
 * completo se asigna al medio elegido.
 *
 * @phpstan-require-extends FormRequest
 */
trait ReglasPago
{
    /**
     * Reglas de la forma de pago y de los valores por medio.
     *
     * @param  list<FormaPago>  $formas  Formas de pago que acepta la venta.
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function reglasPago(array $formas): array
    {
        $medios = array_filter(
            ['valor_efectivo', 'valor_datafono', 'valor_qr'],
            fn (string $medio) => $medio !== 'valor_datafono' || in_array(FormaPago::Datafono, $formas, true),
        );

        $reglas = ['forma_pago' => ['required', Rule::in(array_map(fn (FormaPago $forma) => $forma->value, $formas))]];

        foreach ($medios as $medio) {
            $reglas[$medio] = ['nullable', 'required_if:forma_pago,mixto', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999'];
        }

        return $reglas;
    }

    /**
     * Exige que los valores por medio de un pago mixto sumen el valor de la venta. Solo cuentan los
     * medios que la venta acepta: el valor de un medio que no acepta se descarta al guardar.
     *
     * @param  list<FormaPago>  $formas  Formas de pago que acepta la venta.
     */
    protected function validarSumaMixta(Validator $validator, string $campoValor, array $formas): void
    {
        if ($validator->errors()->isNotEmpty() || $this->input('forma_pago') !== FormaPago::Mixto->value) {
            return;
        }

        $centavos = fn (mixed $valor): int => (int) round((float) $valor * 100);
        $suma = $centavos($this->input('valor_efectivo')) + $centavos($this->input('valor_qr'))
            + (in_array(FormaPago::Datafono, $formas, true) ? $centavos($this->input('valor_datafono')) : 0);

        if ($suma !== $centavos($this->input($campoValor))) {
            $validator->errors()->add('forma_pago', 'Los valores por medio deben sumar el valor total.');
        }
    }

    /**
     * Datos validados con los valores por medio ya resueltos: si el pago no es mixto, todo el valor
     * va al medio elegido.
     *
     * @param  list<FormaPago>  $formas  Formas de pago que acepta la venta.
     * @return array<string, mixed>
     */
    protected function datosConPago(string $campoValor, array $formas): array
    {
        $datos = $this->validated();
        $forma = FormaPago::from($datos['forma_pago']);

        foreach ([FormaPago::Efectivo, FormaPago::Datafono, FormaPago::Qr] as $medio) {
            if (! in_array($medio, $formas, true)) {
                continue;
            }

            $campo = "valor_{$medio->value}";
            $datos[$campo] = $forma === $medio ? $datos[$campoValor] : ($forma === FormaPago::Mixto ? ($datos[$campo] ?? 0) : 0);
        }

        return $datos;
    }
}
