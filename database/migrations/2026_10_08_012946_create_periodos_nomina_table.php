<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('periodos_nomina', function (Blueprint $table) {
            $table->id();
            $table->string('periodicidad_pago', 20);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('borrador');
            $table->foreignId('liquidado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_liquidacion')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamps();

            $table->unique(['periodicidad_pago', 'fecha_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_nomina');
    }
};
