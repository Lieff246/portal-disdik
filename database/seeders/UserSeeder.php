<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed user admin default untuk development dan testing.
     *
     * Password default semua user: "password"
     */
    public function run(): void
    {
        // --- Admin Provinsi ---
        // Bisa akses semua sekolah di seluruh Sulawesi Tengah
        $adminProvinsi = User::firstOrCreate(
            ['email' => 'admin@disdik-sulteng.go.id'],
            [
                'name'     => 'Admin Provinsi',
                'password' => Hash::make('password'),
            ]
        );
        if (!$adminProvinsi->hasRole('admin_provinsi')) {
            $adminProvinsi->assignRole('admin_provinsi');
        }

        // --- Admin Cabang Dinas Wilayah 1 (Palu & Sigi) ---
        // Hanya bisa akses sekolah di kode_kabupaten ["7271", "7210"]
        $adminCabdis1 = User::firstOrCreate(
            ['email' => 'cabdis1@disdik-sulteng.go.id'],
            [
                'name'            => 'Admin Cabdis Wilayah 1',
                'password'        => Hash::make('password'),
                'cabang_dinas_id' => 1, // Wilayah 1 dari tabel cabang_dinas
            ]
        );
        if (!$adminCabdis1->hasRole('admin_cabdis')) {
            $adminCabdis1->assignRole('admin_cabdis');
        }

        // --- Admin Cabang Dinas Wilayah 2 (Parigi Moutong & Donggala) ---
        $adminCabdis2 = User::firstOrCreate(
            ['email' => 'cabdis2@disdik-sulteng.go.id'],
            [
                'name'            => 'Admin Cabdis Wilayah 2',
                'password'        => Hash::make('password'),
                'cabang_dinas_id' => 2,
            ]
        );
        if (!$adminCabdis2->hasRole('admin_cabdis')) {
            $adminCabdis2->assignRole('admin_cabdis');
        }

        // --- Admin Kabupaten Kota Palu ---
        // Hanya bisa akses sekolah dengan kode_kabupaten = "7271"
        $adminKabPalu = User::firstOrCreate(
            ['email' => 'kabkota.palu@disdik-sulteng.go.id'],
            [
                'name'           => 'Admin Kab/Kota Palu',
                'password'       => Hash::make('password'),
                'kode_kabupaten' => '7271', // Kota Palu
            ]
        );
        if (!$adminKabPalu->hasRole('admin_kab_kota')) {
            $adminKabPalu->assignRole('admin_kab_kota');
        }

        // --- Admin Kabupaten Donggala ---
        $adminKabDonggala = User::firstOrCreate(
            ['email' => 'kabkota.donggala@disdik-sulteng.go.id'],
            [
                'name'           => 'Admin Kab/Kota Donggala',
                'password'       => Hash::make('password'),
                'kode_kabupaten' => '7202', // Kab. Donggala
            ]
        );
        if (!$adminKabDonggala->hasRole('admin_kab_kota')) {
            $adminKabDonggala->assignRole('admin_kab_kota');
        }

        $this->command->info('✅ Berhasil membuat 5 user admin.');
        $this->command->info('   Email login: admin@disdik-sulteng.go.id, cabdis1@..., dll');
        $this->command->info('   Password semua: password');
    }
}
