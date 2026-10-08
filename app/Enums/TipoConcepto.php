<?php

namespace App\Enums;

enum TipoConcepto: string
{
    case Devengo = 'devengo';
    case Deduccion = 'deduccion';

    /**
     * Nombre del tipo de concepto para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Devengo => 'Devengo',
            self::Deduccion => 'Deducción',
        };
    }
}
