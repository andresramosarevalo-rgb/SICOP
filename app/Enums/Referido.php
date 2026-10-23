<?php

namespace App\Enums;

enum Referido: string
{
    case R5 = 'r5';
    case Gestor = 'gestor';

    /**
     * Nombre del referido para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::R5 => 'R5',
            self::Gestor => 'Gestor',
        };
    }

    /**
     * Referidos como opciones de un selector: valor y etiqueta.
     *
     * @return list<array{valor: string, etiqueta: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $referido) => ['valor' => $referido->value, 'etiqueta' => $referido->etiqueta()],
            self::cases(),
        );
    }
}
