<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Separar y migrar nombres y apellidos existentes basados en el campo name
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $parts = array_values(array_filter(explode(' ', trim($user->name ?? ''))));
            $count = count($parts);

            $firstName = '';
            $lastName = '';

            if ($count === 1) {
                $firstName = $parts[0];
            } elseif ($count === 2) {
                $firstName = $parts[0];
                $lastName = $parts[1];
            } elseif ($count === 3) {
                $firstName = $parts[0];
                $lastName = "{$parts[1]} {$parts[2]}";
            } elseif ($count >= 4) {
                // Primeros dos como nombres (ej: Jose Alexander) y los restantes como apellidos (ej: Perera Guevara)
                $firstName = "{$parts[0]} {$parts[1]}";
                $lastName = implode(' ', array_slice($parts, 2));
            }

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
