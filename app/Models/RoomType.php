<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RoomType extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'description', 'max_guests'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setLogName('admin')
            ->useLogName('admin');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
