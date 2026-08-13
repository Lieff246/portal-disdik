<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CabangDinasSeeder::class,
            UserSeeder::class,
            DataSekolahImporterSeeder::class,
            SchoolSmaSeeder::class, // ← Data SMA/SMK/SLB (466 records)
        ]);
    }
}
