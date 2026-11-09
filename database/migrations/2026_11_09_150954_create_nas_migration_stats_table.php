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
        Schema::create('nas_migration_stats', function (Blueprint $table) {
            $table->id();
            $table->string('responsable'); // Nombre del ejecutor / operador
            $table->date('fecha'); // Fecha de la guardia
            $table->string('satelite')->default('VRSS-1'); // VRSS-1 o VRSS-2
            $table->string('archivo_id')->nullable(); // ID o identificador de archivo
            $table->decimal('tamano_gb', 14, 4)->default(0); // Tamaño en Gigabytes
            $table->integer('lote_numero')->nullable(); // # correlativo de guardia
            $table->integer('cantidad_archivos')->default(1); // Cantidad de archivos (1 si es fila individual o N si es lote)
            $table->text('observaciones')->nullable();
            
            $table->foreignId('nas_migration_task_id')->nullable()->constrained('nas_migration_tasks')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            // Índices para consultas ágiles en el dashboard
            $table->index(['responsable', 'fecha']);
            $table->index(['satelite', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nas_migration_stats');
    }
};
