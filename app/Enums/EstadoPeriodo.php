<?php

namespace App\Enums;

enum EstadoPeriodo: string
{
    case Borrador = 'borrador';
    case Liquidado = 'liquidado';
    case Cerrado = 'cerrado';

    /**
     * Nombre del estado para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Liquidado => 'Liquidado',
            self::Cerrado => 'Cerrado',
        };
    }
}
