<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Jhonoryza\LaravelPrayertime\Models\City;

trait AdditionSpecificDateTrait
{
    public function getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date): array
    {
        $timeZone = $this->getTimezone($latitude, $longitude);

        // $date format YYYY-MM-DD
        $date = strtotime($date);

        $service = $this->getService();

        return $service->calculateForSingleDate($latitude, $longitude, $timeZone, $date, null, 'longlat');
    }

    public function getFromCityIdOnSpecificDate(string $cityId, string $date): array
    {
        $city = City::query()->where('external_id', $cityId)->first();
        if (! $city instanceof City) {
            return [];
        }
        $latitude  = $city->latitude;
        $longitude = $city->longitude;

        $timeZone = $this->getTimezone($latitude, $longitude);

        // $date format YYYY-MM-DD
        $date = strtotime($date);

        $service = $this->getService();

        return $service->calculateForSingleDate($latitude, $longitude, $timeZone, $date, $city, 'city');
    }
}
