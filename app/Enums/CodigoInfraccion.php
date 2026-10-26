<?php

namespace App\Enums;

enum CodigoInfraccion: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case F = 'F';
    case G = 'G';
    case H = 'H';
    case I = 'I';
    case J = 'J';

    /**
     * Nombre del código de infracción para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return "Tipo {$this->value}";
    }

    /**
     * Códigos de infracción como opciones de un selector: valor y etiqueta.
     *
     * @return list<array{valor: string, etiqueta: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $codigo) => ['valor' => $codigo->value, 'etiqueta' => $codigo->etiqueta()],
            self::cases(),
        );
    }
}
