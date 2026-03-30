<?php

namespace Database\Seeders;

use App\Models\RoleInformasi;
use Illuminate\Database\Seeder;

class RoleInformasiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'role'  => 'admin',
                'judul' => 'Informasi',
                'isi'   => 'Admin hanya dapat melihat Lembar Kerja Evaluasi (read-only). OPD yang mengisi dan mengedit, BPS hanya melihat.',
                'warna' => 'neutral',
            ],
            [
                'role'  => 'opd',
                'judul' => 'Informasi',
                'isi'   => 'OPD dapat mengisi dan mengedit LKE selama status masih draft. Jika sudah final, data dianggap selesai dan hanya bisa dilihat (admin & bps read-only).',
                'warna' => 'neutral',
            ],
            [
                'role'  => 'bps',
                'judul' => 'Informasi',
                'isi'   => 'BPS bertugas melakukan penilaian terhadap LKE yang telah difinalisasi oleh OPD. Gunakan menu Penilaian OPD untuk menilai dan meminta revisi.',
                'warna' => 'neutral',
            ],
        ];

        foreach ($data as $item) {
            RoleInformasi::updateOrCreate(
                ['role' => $item['role']],
                ['judul' => $item['judul'], 'isi' => $item['isi'], 'warna' => $item['warna']]
            );
        }
    }
}
