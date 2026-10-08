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
        Schema::create('novedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->unsignedInteger('cantidad')->nullable();
            $table->foreignId('concepto_nomina_id')->nullable()->constrained('conceptos_nomina')->restrictOnDelete();
            $table->decimal('valor_eventual', 15, 2)->nullable();
            $table->string('observacion')->nullable();
            // La llave foránea a periodos_nomina se agrega cuando exista esa tabla (issue #13).
            $table->unsignedBigInteger('periodo_nomina_id')->nullable()->index();
            $table->timestamps();

            $table->index(['empleado_id', 'fecha_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('novedades');
    }
};
