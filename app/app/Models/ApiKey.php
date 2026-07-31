<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['name', 'key_hash', 'last_used_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Create a new key, returning both the model and the plaintext value.
     * The plaintext is never stored — only its SHA-256 hash is persisted —
     * so this is the only moment the caller can see it.
     */
    public static function generate(string $name): array
    {
        $plainTextKey = 'wotp_'.Str::random(40);

        $model = static::create([
            'name' => $name,
            'key_hash' => hash('sha256', $plainTextKey),
        ]);

        return [$model, $plainTextKey];
    }

    public static function findByPlainTextKey(string $plainTextKey): ?self
    {
        return static::where('key_hash', hash('sha256', $plainTextKey))->first();
    }
}
