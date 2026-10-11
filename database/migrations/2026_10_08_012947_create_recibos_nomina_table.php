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
        Schema::create('recibos_nomina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_nomina_id')->constrained('periodos_nomina')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();
            $table->decimal('valor_salario_base', 15, 2);
            $table->unsignedSmallInteger('dias_liquidados');
            $table->decimal('valor_total_devengado', 15, 2);
            $table->decimal('valor_total_deducciones', 15, 2);
            $table->decimal('valor_neto', 15, 2);
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamps();

            $table->unique(['periodo_nomina_id', 'empleado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos_nomina');
    }
};
