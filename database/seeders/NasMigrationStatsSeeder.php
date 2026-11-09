<?php

namespace Database\Seeders;

use App\Models\NasMigrationStat;
use App\Models\NasMigrationTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NasMigrationStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/nas_migration_stats.json');

        if (!File::exists($jsonPath)) {
            return;
        }

        $records = json_decode(File::get($jsonPath), true);
        if (empty($records)) {
            return;
        }

        $defaultTask = NasMigrationTask::first();
        $taskId = $defaultTask ? $defaultTask->id : null;

        // Truncar para evitar duplicados en re-seed
        NasMigrationStat::truncate();

        $chunks = array_chunk($records, 100);
        $now = now();

        foreach ($chunks as $chunk) {
            $insertData = [];
            foreach ($chunk as $r) {
                $insertData[] = [
                    'responsable' => $r['responsable'],
                    'fecha' => $r['fecha'],
                    'satelite' => $r['satelite'],
                    'archivo_id' => $r['archivo_id'],
                    'tamano_gb' => $r['tamano_gb'],
                    'lote_numero' => $r['lote_numero'] ?? null,
                    'cantidad_archivos' => 1,
                    'observaciones' => 'Migrado en guardia registrada (Cálculo histórico)',
                    'nas_migration_task_id' => $taskId,
                    'user_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            NasMigrationStat::insert($insertData);
        }
    }
}

