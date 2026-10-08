<?php

namespace App\Enums;

enum PeriodicidadPago: string
{
    case Semanal = 'semanal';
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';

    /**
     * Nombre de la periodicidad para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Semanal => 'Semanal',
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
        };
    }
}
