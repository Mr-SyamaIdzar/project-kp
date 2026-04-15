<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model GlobalSetting — key-value store untuk toggle fitur global.
 *
 * Contoh penggunaan:
 *   GlobalSetting::isEnabled('revisi_dokumen_enabled')
 *   GlobalSetting::set('interview_input_enabled', '1')
 */
class GlobalSetting extends Model
{
    protected $table = 'global_settings';

    protected $fillable = ['key', 'value'];

    /** @var array<string,mixed> */
    protected static array $cache = [];

    /**
     * Ambil nilai setting berdasarkan key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }
        $record = static::where('key', $key)->first();
        $val = $record ? $record->value : $default;
        static::$cache[$key] = $val;
        return $val;
    }

    /**
     * Simpan atau update setting.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        static::$cache[$key] = (string) $value;
    }

    /**
     * Cek apakah toggle aktif (value == '1').
     */
    public static function isEnabled(string $key): bool
    {
        return (bool)(int) static::get($key, '0');
    }

    /**
     * Flush runtime cache (untuk testing / setelah update).
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
