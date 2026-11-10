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
            $table->unsignedInteger('total_files_in_batch')->default(0)->after('files_transferred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_migration_logs', function (Blueprint $table) {
            $table->dropColumn('total_files_in_batch');
        });
    }
};
