<?php

namespace Jhonoryza\LaravelPrayertime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'province_external_id',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_external_id', 'external_id');
    }
}
