<?php

namespace App\Enums;

enum Rol: string
{
    case SuperAdmin = 'super_admin';
    case Administrador = 'administrador';
    case AuxiliarNomina = 'auxiliar_nomina';
    case Contador = 'contador';
    case Cajero = 'cajero';

    /**
     * Indica si el rol ve y opera todas las sedes.
     */
    public function esSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Indica si el usuario con este rol debe pertenecer a una sede.
     */
    public function requiereSede(): bool
    {
        return ! $this->esSuperAdmin();
    }

    /**
     * Indica si el rol puede gestionar el módulo de nómina.
     */
    public function puedeGestionarNomina(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Administrador, self::AuxiliarNomina], true);
    }

    /**
     * Indica si el rol puede gestionar el módulo de punto de venta.
     */
    public function puedeGestionarPos(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Administrador], true);
    }

    /**
     * Indica si el rol puede consultar el historial de auditoría.
     */
    public function puedeVerAuditoria(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Administrador], true);
    }

    /**
     * Indica si el rol puede registrar usuarios.
     */
    public function puedeGestionarUsuarios(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Administrador], true);
    }
}
