<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
            ->setLogName('admin')
            ->useLogName('admin');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
