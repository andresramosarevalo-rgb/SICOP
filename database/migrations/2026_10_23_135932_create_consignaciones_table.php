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
        Schema::create('consignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->unique()->constrained('cierres_caja')->restrictOnDelete();
            $table->date('fecha_consignacion');
            $table->decimal('valor_consignado', 15, 2);
            $table->string('referencia', 100);
            $table->string('ruta_soporte');
            $table->string('nombre_soporte');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignaciones');
    }
};
