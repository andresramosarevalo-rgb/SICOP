<?php

namespace App\Enums;

enum FormaCalculo: string
{
    case ValorFijo = 'valor_fijo';
    case Porcentaje = 'porcentaje';
    case Sistema = 'sistema';

    /**
     * Nombre de la forma de cálculo para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::ValorFijo => 'Valor fijo',
            self::Porcentaje => 'Porcentaje del salario',
            self::Sistema => 'Calculado por el sistema',
        };
    }
}
