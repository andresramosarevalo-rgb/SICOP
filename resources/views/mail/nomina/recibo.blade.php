@php
    $pesos = fn ($valor) => '$ '.number_format((float) $valor, 0, ',', '.');
@endphp
<x-mail::message>
# Recibo de pago de nómina

Hola, {{ $empleado->nombres }}. Este es el detalle de tu pago del periodo
**{{ $periodo->fecha_inicio->toDateString() }} a {{ $periodo->fecha_fin->toDateString() }}**.

<x-mail::table>
| Devengos | Cantidad | Valor |
|:---------|---------:|------:|
@foreach ($devengos as $detalle)
| {{ $detalle->descripcion }} | {{ $detalle->cantidad }} | {{ $pesos($detalle->valor_concepto) }} |
@endforeach
| **Total devengado** | | **{{ $pesos($recibo->valor_total_devengado) }}** |
</x-mail::table>

<x-mail::table>
| Deducciones | Cantidad | Valor |
|:------------|---------:|------:|
@foreach ($deducciones as $detalle)
| {{ $detalle->descripcion }} | {{ $detalle->cantidad }} | {{ $pesos($detalle->valor_concepto) }} |
@endforeach
| **Total deducciones** | | **{{ $pesos($recibo->valor_total_deducciones) }}** |
</x-mail::table>

## Neto a pagar: {{ $pesos($recibo->valor_neto) }}

Si tienes alguna duda sobre tu pago, comunícate con el área de nómina.

COVIACOL
</x-mail::message>
