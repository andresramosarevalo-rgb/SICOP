<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El módulo de contabilidad se descartó: las cuentas con rol contador se
     * deshabilitan y pasan al rol de menor alcance, sin borrar ninguna.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('rol', 'contador')
            ->update(['rol' => 'cajero', 'es_activo' => false]);
    }

    public function down(): void
    {
        //
    }
};
