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
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();
            $table->string('modulo', 100);
            $table->string('tabla', 100);
            $table->string('accion', 50); // CREAR, ACTUALIZAR, CAMBIO_ESTADO, ELIMINAR, RESTAURAR
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('registro_codigo', 100)->nullable();
            $table->string('registro_nombre', 255)->nullable();
            $table->json('data_anterior')->nullable();
            $table->json('data_nueva')->nullable();
            $table->json('campos_modificados')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('metodo_http', 10)->nullable();
            $table->timestamps();

            // Indexes for fast querying & filtering
            $table->index(['modulo', 'created_at']);
            $table->index(['tabla', 'registro_id']);
            $table->index(['accion', 'created_at']);
            $table->index('user_id');
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
