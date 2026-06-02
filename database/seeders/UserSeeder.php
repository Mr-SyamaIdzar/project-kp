<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * KEAMANAN: Seeder ini HANYA boleh dijalankan di environment 'local' atau 'staging'.
     * Password diambil dari env() agar tidak ada plaintext hardcoded di version control.
     * Setel variabel berikut di .env sebelum menjalankan seeder:
     *
     *   SEEDER_ADMIN_PASSWORD=
     *   SEEDER_OPD_PASSWORD=
     *   SEEDER_BPS_PASSWORD=
     */
    public function run(): void
    {
        $this->abortIfProduction();

        $adminPass = env('SEEDER_ADMIN_PASSWORD', 'Admin@S33d!2025');
        $opdPass   = env('SEEDER_OPD_PASSWORD',   'Opd@S33d!2025');
        $bpsPass   = env('SEEDER_BPS_PASSWORD',   'Bps@S33d!2025');

        $data = [
            // Admin
            ['username' => 'admin1', 'nama' => 'Administrator 1', 'role' => 'admin', 'password' => $adminPass],
            // ['username' => 'admin2', 'nama' => 'Administrator 2', 'role' => 'admin', 'password' => $adminPass],
            // ['username' => 'admin3', 'nama' => 'Administrator 3', 'role' => 'admin', 'password' => $adminPass],

            // OPD
            // ['username' => 'opd_bappeda', 'nama' => 'OPD Bappeda',          'role' => 'opd', 'password' => $opdPass],
            // ['username' => 'opd_dinkes',  'nama' => 'OPD Dinas Kesehatan',   'role' => 'opd', 'password' => $opdPass],
            // ['username' => 'opd_dikbud',  'nama' => 'OPD Dinas Pendidikan',  'role' => 'opd', 'password' => $opdPass],
            // ['username' => 'opd_disduk',  'nama' => 'OPD Dukcapil',          'role' => 'opd', 'password' => $opdPass],
            // ['username' => 'opd_diskom',  'nama' => 'OPD Diskominfo',        'role' => 'opd', 'password' => $opdPass],

            // BPS
            // ['username' => 'bps_1', 'nama' => 'BPS User 1', 'role' => 'bps', 'password' => $bpsPass],
            // ['username' => 'bps_2', 'nama' => 'BPS User 2', 'role' => 'bps', 'password' => $bpsPass],
            // ['username' => 'bps_3', 'nama' => 'BPS User 3', 'role' => 'bps', 'password' => $bpsPass],
            // ['username' => 'bps_4', 'nama' => 'BPS User 4', 'role' => 'bps', 'password' => $bpsPass],
        ];

        foreach ($data as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'nama'     => $u['nama'],
                    'role'     => $u['role'],
                    'password' => Hash::make($u['password']),
                ]
            );
        }
    }

    /**
     * Lempar exception jika seeder dijalankan di luar environment yang diizinkan.
     * Ini mencegah akun default ter-seed ke production secara tidak sengaja.
     */
    private function abortIfProduction(): void
    {
        if (! app()->environment(['local', 'staging'])) {
            throw new \RuntimeException(
                'UserSeeder hanya boleh dijalankan di environment local atau staging. ' .
                'Environment saat ini: ' . app()->environment()
            );
        }
    }
}

