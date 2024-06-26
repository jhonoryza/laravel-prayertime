<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\CalculationPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\GeniustPrayerTime;

trait AdditionSpecificYearTrait
{
    public function getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year): array
    {
        $timeZone = $this->getTimezone($latitude, $longitude);

        $date    = strtotime($year . '-1-1');
        $endDate = strtotime(($year + 1) . '-1-1');

        $source = config('prayertime.manual_source');
        if ($source == 'praytimes.org') {
            return (new CalculationPrayerTime)->calculate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                null,
                'longlat'
            );
        } elseif ($source == 'geniusts/prayer-times') {
            return (new GeniustPrayerTime)->calculate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                null,
                'longlat'
            );
        }

        return [];
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

        $source = config('prayertime.manual_source');
        if ($source == 'praytimes.org') {
            return (new CalculationPrayerTime)->calculate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'city'
            );
        } elseif ($source == 'geniusts/prayer-times') {
            return (new GeniustPrayerTime)->calculate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'city'
            );
        }

        return [];
    }
}
