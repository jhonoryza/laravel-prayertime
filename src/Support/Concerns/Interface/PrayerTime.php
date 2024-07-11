<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Interface;

/**
 * @method array getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year)
 * @method array getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date)
 * @method array getFromCityIdOnSpecificYear(string $cityId, int $year)
 * @method array getFromCityIdOnSpecificDate(string $cityId, string $date)
 */
interface PrayerTime
{
    public function getBaseUrl(): string;

    public function getProvinces(): array;

    public function getCities(string $provinceId): array;

    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array;
}
