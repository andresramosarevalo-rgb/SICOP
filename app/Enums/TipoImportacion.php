<?php

namespace App\Enums;

use App\Http\Requests\Pos\StoreCursoRequest;
use App\Http\Requests\Pos\StoreSoatRequest;

/**
 * Tipo de venta que se importa desde Excel. Define las columnas de la plantilla.
 */
enum TipoImportacion: string
{
    case Cursos = 'cursos';
    case Soats = 'soats';

    /**
     * Nombre para mostrar en mensajes y en el archivo descargado.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Cursos => 'cursos',
            self::Soats => 'SOAT',
        };
    }

    /**
     * Solicitud de registro cuyas reglas valida cada fila importada.
     *
     * @return class-string<StoreCursoRequest|StoreSoatRequest>
     */
    public function solicitud(): string
    {
        return match ($this) {
            self::Cursos => StoreCursoRequest::class,
            self::Soats => StoreSoatRequest::class,
        };
    }

    /**
     * Columnas de la plantilla, en orden. El tipo define cómo se lee la celda: `texto`, `fecha`, `hora`,
     * `dinero`, `entero` o `lista` (valor de un enum, con las opciones que se muestran en el desplegable).
     *
     * @return list<array{campo: string, encabezado: string, tipo: string, opciones?: list<array{valor: string, etiqueta: string}>, ayuda: string}>
     */
    public function columnas(): array
    {
        $formas = FormaPago::opciones($this === self::Soats ? FormaPago::PARA_SOAT : null);

        $pago = [
            ['campo' => 'forma_pago', 'encabezado' => 'Forma de pago', 'tipo' => 'lista', 'opciones' => $formas, 'ayuda' => 'Elija de la lista.'],
            ['campo' => 'valor_efectivo', 'encabezado' => 'Valor en efectivo', 'tipo' => 'dinero', 'ayuda' => 'Solo si el pago es Mixto.'],
            ...($this === self::Soats ? [] : [
                ['campo' => 'valor_datafono', 'encabezado' => 'Valor en datáfono', 'tipo' => 'dinero', 'ayuda' => 'Solo si el pago es Mixto.'],
            ]),
            ['campo' => 'valor_qr', 'encabezado' => 'Valor en QR', 'tipo' => 'dinero', 'ayuda' => 'Solo si el pago es Mixto.'],
        ];

        $tiposDocumento = array_map(fn (array $o) => ['valor' => $o['valor'], 'etiqueta' => $o['valor']], TipoDocumento::opciones());

        if ($this === self::Soats) {
            return [
                ['campo' => 'fecha', 'encabezado' => 'Fecha', 'tipo' => 'fecha', 'ayuda' => 'Día de la venta, por ejemplo 19/11/2026.'],
                ['campo' => 'tipo_documento', 'encabezado' => 'Tipo de documento', 'tipo' => 'lista', 'opciones' => $tiposDocumento, 'ayuda' => 'Elija de la lista.'],
                ['campo' => 'numero_documento', 'encabezado' => 'Número de documento', 'tipo' => 'texto', 'ayuda' => 'Solo letras y números.'],
                ['campo' => 'nombre_cliente', 'encabezado' => 'Nombre del cliente', 'tipo' => 'texto', 'ayuda' => ''],
                ['campo' => 'celular', 'encabezado' => 'Celular', 'tipo' => 'texto', 'ayuda' => ''],
                ['campo' => 'correo', 'encabezado' => 'Correo', 'tipo' => 'texto', 'ayuda' => ''],
                ['campo' => 'placa', 'encabezado' => 'Placa', 'tipo' => 'texto', 'ayuda' => 'Por ejemplo ABC123.'],
                ['campo' => 'valor_soat', 'encabezado' => 'Valor del SOAT', 'tipo' => 'dinero', 'ayuda' => 'Mayor que cero.'],
                ...$pago,
            ];
        }

        return [
            ['campo' => 'fecha', 'encabezado' => 'Fecha', 'tipo' => 'fecha', 'ayuda' => 'Día del curso, por ejemplo 19/11/2026.'],
            ['campo' => 'tipo_documento', 'encabezado' => 'Tipo de documento', 'tipo' => 'lista', 'opciones' => $tiposDocumento, 'ayuda' => 'Elija de la lista.'],
            ['campo' => 'numero_documento', 'encabezado' => 'Número de documento', 'tipo' => 'texto', 'ayuda' => 'Solo letras y números.'],
            ['campo' => 'nombre_cliente', 'encabezado' => 'Nombre del cliente', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'celular', 'encabezado' => 'Celular', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'correo', 'encabezado' => 'Correo', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'numero_certificado', 'encabezado' => 'Número de certificado', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'numero_comparendo', 'encabezado' => 'Número de comparendo', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'secretaria_transito', 'encabezado' => 'Secretaría de tránsito', 'tipo' => 'texto', 'ayuda' => ''],
            ['campo' => 'hora_inicio', 'encabezado' => 'Hora de inicio', 'tipo' => 'hora', 'ayuda' => 'Formato 24 horas, por ejemplo 08:00.'],
            ['campo' => 'hora_fin', 'encabezado' => 'Hora de fin', 'tipo' => 'hora', 'ayuda' => 'Posterior a la hora de inicio.'],
            ['campo' => 'referido', 'encabezado' => 'Referido', 'tipo' => 'lista', 'opciones' => Referido::opciones(), 'ayuda' => 'Opcional.'],
            ['campo' => 'codigo_infraccion', 'encabezado' => 'Código de infracción', 'tipo' => 'lista', 'opciones' => array_map(fn (array $o) => ['valor' => $o['valor'], 'etiqueta' => $o['valor']], CodigoInfraccion::opciones()), 'ayuda' => 'Letra de la A a la J.'],
            ['campo' => 'valor_curso', 'encabezado' => 'Valor del curso', 'tipo' => 'dinero', 'ayuda' => 'Mayor que cero.'],
            ['campo' => 'porcentaje_descuento', 'encabezado' => 'Descuento (%)', 'tipo' => 'entero', 'ayuda' => '25 o 50.'],
            ...$pago,
        ];
    }
}
