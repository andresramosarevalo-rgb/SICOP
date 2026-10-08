<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case AuxiliarNomina = 'auxiliar_nomina';
    case Contador = 'contador';
    case Cajero = 'cajero';

    /**
     * Indica si el rol puede gestionar el módulo de nómina.
     */
    public function puedeGestionarNomina(): bool
    {
        return in_array($this, [self::Administrador, self::AuxiliarNomina], true);
    }
}
