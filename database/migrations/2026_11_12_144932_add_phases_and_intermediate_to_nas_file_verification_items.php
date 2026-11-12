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
        Schema::table('nas_file_verification_items', function (Blueprint $table) {
            // Fase 1: Synology -> Carpeta Compartida Intermedia (MIGRACION_SYNOLOGY)
            $table->unsignedBigInteger('shared_size')->nullable()->after('synology_mtime');
            $table->timestamp('shared_mtime')->nullable()->after('shared_size');
            $table->string('phase1_status', 50)->default('confirmed')->after('shared_mtime'); // confirmed, size_mismatch, missing, pending

            // Fase 2: Carpeta Compartida Intermedia -> RAWDATA Inspur
            $table->string('phase2_status', 50)->default('confirmed')->after('inspur_mtime'); // confirmed, size_mismatch, missing, pending
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nas_file_verification_items', function (Blueprint $table) {
            $table->dropColumn(['shared_size', 'shared_mtime', 'phase1_status', 'phase2_status']);
        });
    }
};
