<?php

namespace App\Enums;

enum FormaPago: string
{
    case Efectivo = 'efectivo';
    case Datafono = 'datafono';
    case Qr = 'qr';
    case Mixto = 'mixto';

    /**
     * Formas de pago que acepta el SOAT: no se paga por datáfono.
     *
     * @var list<self>
     */
    public const PARA_SOAT = [self::Efectivo, self::Qr, self::Mixto];

    /**
     * Nombre de la forma de pago para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Datafono => 'Datáfono',
            self::Qr => 'QR',
            self::Mixto => 'Mixto',
        };
    }

    /**
     * Formas de pago como opciones de un selector: valor y etiqueta.
     *
     * @param  list<self>|null  $formas  Formas a ofrecer; todas si no se indica.
     * @return list<array{valor: string, etiqueta: string}>
     */
    public static function opciones(?array $formas = null): array
    {
        return array_map(
            fn (self $forma) => ['valor' => $forma->value, 'etiqueta' => $forma->etiqueta()],
            $formas ?? self::cases(),
        );
    }
}
