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
        Schema::table('nas_migration_tasks', function (Blueprint $table) {
            // Ubicación base en Inspur y subdirectorio seleccionado
            $table->string('inspur_base_path')->default('/HS_IPS_PRIVATE/RDP_FTP/RAWDATA')->after('target_path');
            $table->string('inspur_target_dir')->default('VRSS-1')->after('inspur_base_path');
            
            // Conexión y carpeta compartida en Inspur
            $table->string('inspur_host')->nullable()->default('192.168.10.180')->after('inspur_target_dir');
            $table->integer('inspur_port')->default(22)->after('inspur_host');
            $table->string('inspur_user')->nullable()->default('root')->after('inspur_port');
            $table->text('inspur_password')->nullable()->after('inspur_user');
            $table->string('inspur_shared_folder')->default('/HS_SHARE/MIGRACION_SYNOLOGY')->after('inspur_password');
            $table->boolean('auto_move_to_inspur')->default(true)->after('inspur_shared_folder');
        });

        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->string('source_path_used')->nullable()->after('files_transferred');
            $table->string('inspur_target_dir_used')->nullable()->after('source_path_used');
            $table->integer('files_moved')->default(0)->after('inspur_target_dir_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_migration_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'inspur_base_path',
                'inspur_target_dir',
                'inspur_host',
                'inspur_port',
                'inspur_user',
                'inspur_password',
                'inspur_shared_folder',
                'auto_move_to_inspur',
            ]);
        });

        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->dropColumn([
                'source_path_used',
                'inspur_target_dir_used',
                'files_moved',
            ]);
        });
    }
};

