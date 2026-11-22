<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un recibo reemplazado por una nueva liquidación se conserva como no vigente: solo puede haber
     * un recibo vigente por empleado en cada periodo, pero los anteriores se mantienen para consultarlos.
     */
    public function up(): void
    {
        Schema::table('recibos_nomina', function (Blueprint $table) {
            $table->dropUnique(['periodo_nomina_id', 'empleado_id']);
            $table->boolean('es_vigente')->default(true)->after('valor_neto');
            $table->timestamp('reemplazado_en')->nullable()->after('es_vigente');
        });

        DB::statement('create unique index recibos_nomina_vigente_unique on recibos_nomina (periodo_nomina_id, empleado_id) where es_vigente');
    }

    public function down(): void
    {
        DB::statement('drop index recibos_nomina_vigente_unique');
        DB::table('recibos_nomina')->where('es_vigente', false)->delete();

        Schema::table('recibos_nomina', function (Blueprint $table) {
            $table->dropColumn(['es_vigente', 'reemplazado_en']);
            $table->unique(['periodo_nomina_id', 'empleado_id']);
        });
    }
};
