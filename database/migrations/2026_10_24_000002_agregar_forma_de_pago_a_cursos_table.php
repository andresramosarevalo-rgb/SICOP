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
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('forma_pago', 20)->default('efectivo');
            $table->decimal('valor_efectivo', 15, 2)->default(0);
            $table->decimal('valor_datafono', 15, 2)->default(0);
            $table->decimal('valor_qr', 15, 2)->default(0);
        });

        DB::table('cursos')->update(['valor_efectivo' => DB::raw('valor_curso')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn(['forma_pago', 'valor_efectivo', 'valor_datafono', 'valor_qr']);
        });
    }
};
