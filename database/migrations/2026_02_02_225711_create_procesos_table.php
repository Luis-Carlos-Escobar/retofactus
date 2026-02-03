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
        Schema::create('procesos', function (Blueprint $table) {
            $table->id();

            //relaciones
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('marca_id')->constrained()->onDelete('cascade');
            $table->foreignId('modelo_id')->constrained()->onDelete('cascade');
            $table->foreignId('pulgada_id')->constrained()->onDelete('cascade');

            //datos del proceso
            $table->string('falla');
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('En proceso');
            $table->date('fecha_ingreso')->useCurrent();
            $table->date('fecha_salida')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos');
    }
};
