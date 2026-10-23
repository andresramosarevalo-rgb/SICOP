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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modulo', 20);
            $table->string('evento', 15);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('etiqueta', 60);
            $table->string('descripcion');
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->json('cambios');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
