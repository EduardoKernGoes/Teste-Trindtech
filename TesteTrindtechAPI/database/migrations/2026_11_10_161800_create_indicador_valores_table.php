<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicador_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')
                ->cascadeOnDelete();
            $table->date('data_referencia');
            $table->decimal('valor', 8, 2);
            $table->timestamps();
            $table->unique(['indicador_id', 'data_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicador_valores');
    }
};
