<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IspSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get setting value by key with optional default fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();

        return ($setting && trim((string) $setting->value) !== '') ? $setting->value : $default;
    }

    /**
     * Set or update setting value by key.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get office reference coordinates for network map.
     */
    public static function getOfficeLocation(): array
    {
        $jsonPath = config_path('settings.json');
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (! empty($json['office_lat']) && ! empty($json['office_lng'])) {
                return [
                    'name' => $json['office_name'] ?? 'Kantor Pusat ISP',
                    'lat' => (float) $json['office_lat'],
                    'lng' => (float) $json['office_lng'],
                    'zoom' => (int) ($json['default_zoom'] ?? 16),
                ];
            }
        }

        return [
            'name' => static::get('company_name', 'Kantor Pusat ISP'),
            'lat' => (float) static::get('office_lat', -6.2087634),
            'lng' => (float) static::get('office_lng', 106.845599),
            'zoom' => 16,
        ];
    }
}
