<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un periodo pertenece a una sede; sin sede liquida a todos los empleados de todas las sedes.
     */
    public function up(): void
    {
        Schema::table('periodos_nomina', function (Blueprint $table) {
            $table->dropUnique(['periodicidad_pago', 'fecha_inicio']);
            $table->foreignId('sede_id')->nullable()->after('id')
                ->constrained('sedes')->restrictOnDelete();
        });

        DB::statement('alter table periodos_nomina add constraint periodos_nomina_sede_periodicidad_fecha_unique unique nulls not distinct (sede_id, periodicidad_pago, fecha_inicio)');
    }

    public function down(): void
    {
        DB::statement('alter table periodos_nomina drop constraint periodos_nomina_sede_periodicidad_fecha_unique');

        Schema::table('periodos_nomina', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
            $table->unique(['periodicidad_pago', 'fecha_inicio']);
        });
    }
};
