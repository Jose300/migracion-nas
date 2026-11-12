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
        Schema::table('nas_file_verifications', function (Blueprint $table) {
            $table->string('current_orbit')->nullable()->after('status');
            $table->integer('files_scanned')->default(0)->after('current_orbit');
            $table->integer('total_files_to_scan')->default(0)->after('files_scanned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_file_verifications', function (Blueprint $table) {
            $table->dropColumn(['current_orbit', 'files_scanned', 'total_files_to_scan']);
        });
    }
};
