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
        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->string('phase_synology_status')->default('pending')->after('files_transferred'); // pending, completed, failed
            $table->string('phase_inspur_status')->default('pending')->after('phase_synology_status'); // pending, completed, failed, skipped
            $table->text('phase_details')->nullable()->after('phase_inspur_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->dropColumn(['phase_synology_status', 'phase_inspur_status', 'phase_details']);
        });
    }
};
