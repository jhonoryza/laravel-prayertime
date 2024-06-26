<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\AdditionSpecificDateTrait;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\AdditionSpecificYearTrait;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\ProvinceCityTrait;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\SupportsTrait;

class ManualPrayerTime implements PrayerTime
{
    use AdditionSpecificDateTrait;
    use AdditionSpecificYearTrait;
    use ProvinceCityTrait;
    use SupportsTrait;

    public function getBaseUrl(): string
    {
        return '';
    }

    /**
     * @throws \Exception
     */
    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
    {
        unset($month);

        $city = City::query()->where('external_id', $cityId)->first();
        if (! $city instanceof City) {
            return [];
        }
        $latitude  = $city->latitude;
        $longitude = $city->longitude;

        $timeZone = $this->getTimezone($latitude, $longitude);

        $date    = strtotime($year . '-1-1');
        $endDate = strtotime(($year + 1) . '-1-1');

        $service = $this->getService();

        return $service->calculate(
            $latitude,
            $longitude,
            $timeZone,
            $date,
            $endDate,
            $city,
            'database'
        );
    }
}
