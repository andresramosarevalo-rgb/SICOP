<?php

namespace App\Enums;

enum TipoCuentaBancaria: string
{
    case Ahorros = 'ahorros';
    case Corriente = 'corriente';
    case Pensional = 'pensional';
    case Nomina = 'nomina';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ahorros => 'Ahorros',
            self::Corriente => 'Corriente',
            self::Pensional => 'Pensional',
            self::Nomina => 'Nómina',
        };
    }

    /**
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
