<?php

namespace App\Enums;

enum TipoContrato: string
{
    case TerminoIndefinido = 'termino_indefinido';
    case TerminoFijo = 'termino_fijo';
    case ObraLabor = 'obra_labor';
    case Aprendizaje = 'aprendizaje';

    /**
     * Nombre del tipo de contrato para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::TerminoIndefinido => 'Término indefinido',
            self::TerminoFijo => 'Término fijo',
            self::ObraLabor => 'Obra o labor',
            self::Aprendizaje => 'Aprendizaje',
        };
    }
}
