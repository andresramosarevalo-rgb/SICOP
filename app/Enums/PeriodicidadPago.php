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

    /**
     * Días que se liquidan en un periodo completo, según el mes comercial de 30 días.
     */
    public function dias(): int
    {
        return match ($this) {
            self::Semanal => 7,
            self::Quincenal => 15,
            self::Mensual => 30,
        };
    }
}
