<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\MyQuran\Traits;

use Carbon\Carbon;

trait SupportsTrait
{
    public function normalizeDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    public function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }

    public function getBaseUrl(): string
    {
        return config('prayertime.base_uri');
    }
}
