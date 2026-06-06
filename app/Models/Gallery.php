<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Gallery extends Model
{
    use LogsActivity;

    protected $table = 'gallery';

    protected $fillable = ['image', 'caption', 'sort_order'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setLogName('admin')
            ->useLogName('admin');
    }
}
