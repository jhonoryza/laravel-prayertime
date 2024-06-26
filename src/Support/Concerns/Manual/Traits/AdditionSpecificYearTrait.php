<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Jhonoryza\LaravelPrayertime\Models\City;

trait AdditionSpecificYearTrait
{
    public function getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year): array
    {
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
            null,
            'longlat'
        );
    }

    public function getFromCityIdOnSpecificYear(string $cityId, int $year): array
    {
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
            'city'
        );
    }
}
