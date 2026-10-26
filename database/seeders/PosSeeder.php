<?php

namespace Database\Seeders;

use App\Enums\CodigoInfraccion;
use App\Models\Sede;
use App\Models\TarifaGestor;
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
     * Pago al gestor por código de infracción, valor de referencia que el gerente ajusta en la
     * pantalla de tarifas. Los códigos sin valor confirmado quedan en cero.
     *
     * @var array<string, int>
     */
    public const TARIFAS_BASE_GESTOR = ['B' => 10000, 'C' => 20000, 'D' => 30000, 'H' => 5000];

    /**
     * Carga las sedes de ejemplo. Lo que ya existe no se modifica.
     */
    public function run(): void
    {
        foreach (self::SEDES as $sede) {
            Sede::firstOrCreate(['nombre' => $sede['nombre']], $sede);
        }

        foreach (CodigoInfraccion::cases() as $codigo) {
            TarifaGestor::firstOrCreate(
                ['codigo_infraccion' => $codigo, 'sede_id' => null],
                ['valor_pago_gestor' => self::TARIFAS_BASE_GESTOR[$codigo->value] ?? 0],
            );
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
