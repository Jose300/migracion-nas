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
        Schema::create('nas_migration_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Conexión SSH a la Synology (Host ejecutor)
            $table->string('nas_host')->default('192.168.3.124');
            $table->integer('nas_port')->default(22);
            $table->string('ssh_user')->default('admin');
            $table->string('ssh_auth_type')->default('password'); // password o key
            $table->text('ssh_password')->nullable(); // Guardado cifrado
            $table->string('ssh_key_path')->nullable();
            
            // Rutas de datos
            $table->string('source_path')->default('/volume1/datos/');
            $table->string('target_path')->default('/volume1/Destino_Inspur/');
            
            // Parámetros de rsync
            $table->string('rsync_options')->default('-avh --stats --progress');
            $table->boolean('delete_after_sync')->default(false); // Flag --delete
            
            // Programación automática
            $table->string('schedule_frequency')->default('manual'); // manual, hourly, daily, twiceDaily, weekly
            $table->string('schedule_time')->nullable()->default('02:00'); // Hora para ejecuciones diarias
            
            // Estados y métricas
            $table->enum('status', ['idle', 'running', 'completed', 'failed'])->default('idle');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        Schema::create('nas_migration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_migration_task_id')->constrained('nas_migration_tasks')->onDelete('cascade');
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->enum('trigger_type', ['manual', 'scheduled'])->default('manual');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Estadísticas parseadas de rsync
            $table->integer('files_transferred')->nullable()->default(0);
            $table->bigInteger('total_bytes_transferred')->nullable()->default(0);
            $table->string('human_transferred_size')->nullable();
            $table->string('speed')->nullable();
            
            $table->text('summary')->nullable();
            $table->longText('output')->nullable(); // Consola completa
            $table->text('error_message')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nas_migration_logs');
        Schema::dropIfExists('nas_migration_tasks');
    }
};
