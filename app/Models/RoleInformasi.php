<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleInformasi extends Model
{
    protected $table = 'role_informasi';

    protected $fillable = [
        'role',
        'judul',
        'isi',
    ];

    /**
     * Ambil informasi untuk role tertentu,
     * atau buat default jika belum ada.
     */
    public static function forRole(string $role): self
    {
        return self::firstOrCreate(
            ['role' => $role],
            [
                'judul' => 'Informasi',
                'isi'   => 'Tidak ada informasi tersedia.',
            ]
        );
    }
}
