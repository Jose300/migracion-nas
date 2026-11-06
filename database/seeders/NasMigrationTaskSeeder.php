<?php

namespace Database\Seeders;

use App\Models\NasMigrationTask;
use Illuminate\Database\Seeder;

class NasMigrationTaskSeeder extends Seeder
{
    public function run(): void
    {
        NasMigrationTask::updateOrCreate(
            ['nas_host' => '192.168.3.124'],
            [
                'name' => 'Sincronización Synology FAO -> Inspur InStorage',
                'description' => 'Migración automatizada asíncrona vía enlace LAN dedicado (192.168.10.50 a 192.168.10.180)',
                'nas_port' => 22,
                'ssh_user' => 'admin',
                'ssh_auth_type' => 'password',
                'source_path' => '/volume1/dae-RowData/',
                'target_path' => '/volume1/dae-RowData/MIGRACION_SYNOLOGY/',
                'inspur_base_path' => '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA',
                'inspur_target_dir' => 'VRSS-1',
                'inspur_host' => '192.168.10.180',
                'inspur_shared_folder' => '/HS_SHARE/MIGRACION_SYNOLOGY',
                'auto_move_to_inspur' => true,
                'rsync_options' => '-avh --stats --progress --timeout=300 --partial',
                'schedule_frequency' => 'hourly',
                'schedule_time' => '02:00',
                'status' => 'idle',
                'is_active' => true,
            ]
        );
    }
}
