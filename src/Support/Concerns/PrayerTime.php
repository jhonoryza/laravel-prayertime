<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns;

/**
 * @method getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year)
 * @method getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date)
 * @method getFromCityIdOnSpecificYear(string $cityId, int $year)
 * @method getFromCityIdOnSpecificDate(string $cityId, string $date)
 */
interface PrayerTime
{
    public function getBaseUrl(): string;

    public function getProvinces(): array;

    public function getCities(string $provinceId): array;

    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array;
}
