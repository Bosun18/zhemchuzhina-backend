<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class News extends Model
{
    use LogsActivity;

    protected $fillable = ['title', 'content', 'image', 'is_published', 'published_at'];

    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setLogName('admin')
            ->useLogName('admin');
    }
}
