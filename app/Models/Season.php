<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Season extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'date_from', 'date_to'];

    protected $casts = ['date_from' => 'date', 'date_to' => 'date'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('admin');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
