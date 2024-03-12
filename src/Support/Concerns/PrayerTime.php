<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns;

interface PrayerTime
{
    public function getBaseUrl(): string;

    public function getProvinces(): array;

    public function getCities(string $provinceId): array;

    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array;
}
