<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case CedulaCiudadania = 'CC';
    case CedulaExtranjeria = 'CE';
    case Pasaporte = 'PA';
    case PermisoProteccionTemporal = 'PPT';

    /**
     * Nombre del tipo de documento para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::CedulaCiudadania => 'Cédula de ciudadanía',
            self::CedulaExtranjeria => 'Cédula de extranjería',
            self::Pasaporte => 'Pasaporte',
            self::PermisoProteccionTemporal => 'Permiso por protección temporal',
        };
    }

    /**
     * Tipos de documento como opciones de un selector: valor y etiqueta.
     *
     * @return list<array{valor: string, etiqueta: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $tipo) => ['valor' => $tipo->value, 'etiqueta' => $tipo->etiqueta()],
            self::cases(),
        );
    }
}
