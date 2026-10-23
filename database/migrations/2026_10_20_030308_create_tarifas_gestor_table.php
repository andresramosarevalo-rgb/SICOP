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
        Schema::create('tarifas_gestor', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_infraccion', 1);
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->restrictOnDelete();
            $table->decimal('valor_pago_gestor', 15, 2);
            $table->timestamps();

            $table->unique(['codigo_infraccion', 'sede_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas_gestor');
    }
};
