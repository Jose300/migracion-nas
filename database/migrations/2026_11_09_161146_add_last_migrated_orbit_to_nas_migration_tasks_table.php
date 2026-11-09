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
            $table->string('last_migrated_orbit')->nullable()->after('inspur_target_dir');
            $table->timestamp('last_migrated_orbit_at')->nullable()->after('last_migrated_orbit');
        });

        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->string('last_migrated_orbit_in_batch')->nullable()->after('files_moved');
            $table->integer('orbits_transferred')->default(0)->after('last_migrated_orbit_in_batch');
            $table->integer('orbits_skipped')->default(0)->after('orbits_transferred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_migration_tasks', function (Blueprint $table) {
            $table->dropColumn(['last_migrated_orbit', 'last_migrated_orbit_at']);
        });

        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->dropColumn(['last_migrated_orbit_in_batch', 'orbits_transferred', 'orbits_skipped']);
        });
    }
};
