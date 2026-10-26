<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    /**
     * Sedes de ejemplo. Las reales las registra el gerente desde el módulo POS.
     *
     * @var array<int, array{nombre: string, ciudad: string, direccion: string}>
     */
    public const SEDES = [
        ['nombre' => 'Sede Villavicencio', 'ciudad' => 'Villavicencio', 'direccion' => 'Calle 15 # 30-20'],
        ['nombre' => 'Sede Bogotá', 'ciudad' => 'Bogotá', 'direccion' => 'Carrera 10 # 20-30'],
    ];

    /**
     * Carga las sedes de ejemplo. Lo que ya existe no se modifica.
     */
    public function run(): void
    {
        foreach (self::SEDES as $sede) {
            Sede::firstOrCreate(['nombre' => $sede['nombre']], $sede);
        }
    }
}
