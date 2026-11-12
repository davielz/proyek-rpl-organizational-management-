<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AiConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'model',
        'base_url',
        'timeout',
        'max_tokens',
        'temperature',
        'is_active',
        'is_default',
        'description',
    ];

    protected $casts = [
        'timeout' => 'integer',
        'max_tokens' => 'integer',
        'temperature' => 'float',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the currently active AI configuration.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first()
            ?? self::where('is_default', true)->first();
    }

    /**
     * Activate this AI configuration and deactivate all others.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            self::query()->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    /**
     * Get masked API key for safe UI display.
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (empty($this->api_key)) {
            return 'Belum diatur';
        }

        $length = strlen($this->api_key);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return substr($this->api_key, 0, 6).'••••••••'.substr($this->api_key, -4);
    }
}
