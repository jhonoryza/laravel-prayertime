<?php

namespace Jhonoryza\LaravelPrayertime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prayertime extends Model
{
    protected $fillable = [
        'city_external_id',
        'prayer_at',
        'imsak',
        'subuh',
        'terbit',
        'dhuha',
        'dzuhur',
        'ashar',
        'maghrib',
        'isya',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_external_id', 'external_id');
    }
}
