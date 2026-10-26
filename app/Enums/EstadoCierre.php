<?php

namespace App\Enums;

enum EstadoCierre: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    /**
     * Nombre del estado para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }
}
