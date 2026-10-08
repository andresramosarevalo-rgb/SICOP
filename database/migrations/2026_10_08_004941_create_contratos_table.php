<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->string('tipo_contrato', 30);
            $table->string('periodicidad_pago', 20);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('valor_salario_base', 15, 2);
            $table->boolean('es_vigente')->default(true);
            $table->timestamps();
        });

        // Un empleado solo puede tener un contrato vigente.
        DB::statement('CREATE UNIQUE INDEX contratos_empleado_vigente_unique ON contratos (empleado_id) WHERE es_vigente');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
