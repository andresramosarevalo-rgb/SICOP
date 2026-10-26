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
        Schema::create('soats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->date('fecha');
            $table->string('tipo_documento', 3);
            $table->string('numero_documento', 20);
            $table->string('nombre_cliente', 200);
            $table->decimal('valor_soat', 15, 2);
            $table->timestamps();

            $table->index(['sede_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soats');
    }
};
