<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\User;
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

        $this->crearAccesoDeEjemplo(Sede::where('nombre', 'Sede Villavicencio')->firstOrFail());
    }

    /**
     * Usuario de ejemplo de una sede (contraseña `password`). Los reales los crea el gerente.
     */
    private function crearAccesoDeEjemplo(Sede $sede): void
    {
        if ($sede->usuario()->exists()) {
            return;
        }

        User::factory()->paraSede($sede)->create([
            'name' => $sede->nombre,
            'email' => 'villavicencio@example.com',
        ]);
    }
}
