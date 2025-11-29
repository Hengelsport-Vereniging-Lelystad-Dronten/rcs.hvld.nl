<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ----- GEBRUIKERS DATA (FIXED) -----
        DB::table('users')->insert([
            // 1. Ronny Roethof (Beheerder)
            [
                'name' => 'Ronny Roethof',
                'email' => 'ronny.roethof@hvld.nl',
                'password' => '$2y$12$58h9H10ZBUlFP0.71qMzwuQyKN40SbNb.6L1ac4eegDSIpyPyoiai',
                'role' => 'Beheerder',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 2. Edwin Roethof (Beheerder)
            [
                'name' => 'Edwin Roethof',
                'email' => 'edwin.roethof@hvld.nl',
                'password' => '$2y$12$fMoDiSEWt1h/JDQJeMtAJ.PudYkDZuVZi34zPWc5Gq43zcx/lYXwG',
                'role' => 'Beheerder',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 2. Jaap de Jager (Beheerder)
            [
                'name' => 'Jaap de Jager',
                'email' => 'jaap.de.jager@hvld.nl',
                'password' => '$2y$12$2u8AGqkX78HcpBv2TP.6DOTw3ThKxpGEdVLAfzHqTv.8pe/VGgwrG',
                'role' => 'Beheerder',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 4 t/m 7: Controleurs
            [
                'name' => 'Johan Govers',
                'email' => 'johan.govers@hvld.nl',
                'password' => '$2y$12$JOzKlNU9yillW7xRmHul2eSG8PZ26g7YJEtP3Nk..opOJDF4apLHS',
                'role' => 'Controleur',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ger Gilissen',
                'email' => 'ger.gilissen@hvld.nl',
                'password' => '$2y$12$vIoSaE.Tjzdi9zjWhL6sIOiIFndv8s6B98LSXO55LfB.9Ee5g0RyS',
                'role' => 'Controleur',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Erwin Rikkelman',
                'email' => 'erwin.rikkelman@hvld.nl',
                'password' => '$2y$12$Uv/ybrAwbft9GokdCtFHAut6SLw.KPqZlZZJqRAnKUzwW.QCfUQnC',
                'role' => 'Controleur',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ 
                'name' => 'Nick Muller',
                'email' => 'nick.muller@hvld.nl',
                'password' => '$2y$12$6jVIbeJpo6TjTzkOPhjjueaKuZUbOGZ/48.X64pfSbSzJpBwNsLNO',
                'role' => 'Controleur',
                'actief' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 2. Mathieu Jansen (Coordinator)
            [
                'name' => 'Mathieu Jansen',
                'email' => 'mathieu.jansen@hvld.nl',
                'password' => '$2y$12$zw64NoM75JQ6axRuE7VM9uU26eGGN8aWbXxNwRaUWuHpBClaTgVGe',
                'role' => 'Coordinator',
                'actief' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);  

        // Roep de Seeder voor Strafmaten aan
        $this->call(StrafmaatSeeder::class);
        // Roep de Seeder voor Overtreding Codes aan
        $this->call(OvertredingTypeSeeder::class);
        // Roep de Seeder voor Wateren aan
        $this->call(WaterSeeder::class);
    }
}
