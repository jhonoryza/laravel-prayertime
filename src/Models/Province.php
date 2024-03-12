<?php

namespace Jhonoryza\LaravelPrayertime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'external_id',
        'name',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_external_id', 'external_id');
    }
}
