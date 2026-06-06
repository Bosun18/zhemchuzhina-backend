<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('admin');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->first()?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
