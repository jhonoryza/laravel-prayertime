<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\CalculationPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\GeniustPrayerTime;

trait AdditionSpecificDateTrait
{
    public function getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date): array
    {
        $timeZone = $this->getTimezone($latitude, $longitude);

        // $date format YYYY-MM-DD
        $date = strtotime($date);

        $source = config('prayertime.manual_source');
        if ($source == 'praytimes.org') {
            return (new CalculationPrayerTime)->calculateForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                null,
                'longlat'
            );
        } elseif ($source == 'geniusts/prayer-times') {
            return (new GeniustPrayerTime)->calculateForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                null,
                'longlat'
            );
        }

        return [];
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

        $source = config('prayertime.manual_source');
        if ($source == 'praytimes.org') {
            return (new CalculationPrayerTime)->calculateForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $city,
                'city'
            );
        } elseif ($source == 'geniusts/prayer-times') {
            return (new GeniustPrayerTime)->calculateForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $city,
                'city'
            );
        }

        return [];
    }
}
