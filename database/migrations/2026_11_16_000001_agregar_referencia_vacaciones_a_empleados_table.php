<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->integer('dias_vacaciones_historicos')->nullable();
            $table->date('fecha_corte_vacaciones')->nullable();
        });

        DB::statement('ALTER TABLE empleados ADD CONSTRAINT empleados_referencia_vacaciones_check CHECK (
            (dias_vacaciones_historicos IS NULL AND fecha_corte_vacaciones IS NULL)
            OR (dias_vacaciones_historicos IS NOT NULL AND dias_vacaciones_historicos >= 0 AND fecha_corte_vacaciones IS NOT NULL)
        )');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE empleados DROP CONSTRAINT empleados_referencia_vacaciones_check');

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['dias_vacaciones_historicos', 'fecha_corte_vacaciones']);
        });
    }
};
