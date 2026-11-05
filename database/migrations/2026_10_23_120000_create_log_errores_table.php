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
        Schema::create('log_errores', function (Blueprint $table) {
            $table->id();
            
            // Nivel de severidad y clasificación
            $table->string('nivel', 20)->default('ERROR'); // EMERGENCY, ALERT, CRITICAL, ERROR, WARNING
            $table->string('clase_error', 255)->nullable(); // e.g. QueryException, DivisionByZeroError, etc.
            $table->text('mensaje'); // Mensaje descriptivo del error
            $table->string('codigo', 50)->nullable(); // Código de error / SQLSTATE / status code
            
            // Ubicación en el código fuente
            $table->string('archivo', 500)->nullable();
            $table->integer('linea')->nullable();
            $table->longText('trace')->nullable(); // Stack trace completo
            
            // Contexto HTTP y Petición
            $table->string('url', 1000)->nullable();
            $table->string('metodo_http', 10)->nullable(); // GET, POST, PUT, DELETE, CLI
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable(); // Payload/parámetros sanitizados
            $table->json('headers')->nullable(); // Cabeceras relevantes
            
            // Contexto del Usuario Afectado
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->string('user_email', 150)->nullable();
            
            // Gestión de Resolución
            $table->string('estado', 30)->default('pendiente'); // pendiente, en_revision, resuelto, ignorado
            $table->text('solucion_notas')->nullable(); // Notas de análisis o solución técnica
            $table->foreignId('resuelto_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_at')->nullable();
            
            // Identificador de recurrencia
            $table->string('hash', 64)->nullable()->index(); // Hash MD5 para agrupar o identificar errores recurrentes
            
            $table->timestamps();
            
            // Índices para optimizar consultas de monitoreo
            $table->index(['created_at', 'nivel']);
            $table->index(['estado', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_errores');
    }
};
