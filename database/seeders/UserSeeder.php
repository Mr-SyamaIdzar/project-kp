<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;  
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $data = [
        // Admin
        ['username' => 'admin1', 'nama' => 'Administrator 1', 'role' => 'admin', 'password' => 'Admin@1234'],
        ['username' => 'admin2', 'nama' => 'Administrator 2', 'role' => 'admin', 'password' => 'Admin@1234'],
        ['username' => 'admin3', 'nama' => 'Administrator 3', 'role' => 'admin', 'password' => 'Admin@1234'],

        // OPD
        ['username' => 'opd_bappeda',  'nama' => 'OPD Bappeda',  'role' => 'opd', 'password' => 'Opd@12345'],
        ['username' => 'opd_dinkes',   'nama' => 'OPD Dinas Kesehatan', 'role' => 'opd', 'password' => 'Opd@12345'],
        ['username' => 'opd_dikbud',   'nama' => 'OPD Dinas Pendidikan', 'role' => 'opd', 'password' => 'Opd@12345'],
        ['username' => 'opd_disduk',   'nama' => 'OPD Dukcapil', 'role' => 'opd', 'password' => 'Opd@12345'],
        ['username' => 'opd_diskom',   'nama' => 'OPD Diskominfo', 'role' => 'opd', 'password' => 'Opd@12345'],

        // BPS
        ['username' => 'bps_1', 'nama' => 'BPS User 1', 'role' => 'bps', 'password' => 'Bps@12345'],
        ['username' => 'bps_2', 'nama' => 'BPS User 2', 'role' => 'bps', 'password' => 'Bps@12345'],
        ['username' => 'bps_3', 'nama' => 'BPS User 3', 'role' => 'bps', 'password' => 'Bps@12345'],
        ['username' => 'bps_4', 'nama' => 'BPS User 4', 'role' => 'bps', 'password' => 'Bps@12345'],
    ];

    foreach ($data as $u) {
        User::updateOrCreate(
            ['username' => $u['username']], // pastikan unique
            [
                'nama' => $u['nama'],
                'role' => $u['role'],
                'password' => Hash::make($u['password']),
            ]
        );
    }
}
}
