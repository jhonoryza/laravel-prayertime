<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns;

use GeniusTS\PrayerTimes\Prayer;
use Jhonoryza\LaravelPrayertime\Models\City;

trait ManualAdditionTrait
{
    public function getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year): array
    {
        $timeZone = $this->getTimezone($latitude, $longitude);

        $date    = strtotime($year . '-1-1');
        $endDate = strtotime(($year + 1) . '-1-1');

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateUsingThirdPartyLib(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                null,
                'longlat'
            ) : $this->calculateManual(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                null,
                'longlat'
            );
    }

    public function getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date): array
    {
        $timeZone = $this->getTimezone($latitude, $longitude);

        // $date format YYYY-MM-DD
        $date = strtotime($date);

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateUsingThirdPartyLibForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                null,
                'longlat'
            ) : $this->calculateManualForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
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

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateUsingThirdPartyLib(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'city'
            ) : $this->calculateManual(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'city'
            );
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

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateUsingThirdPartyLibForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $city,
                'city'
            ) : $this->calculateManualForSingleDate(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $city,
                'city'
            );
    }

    protected function calculateManualForSingleDate(float $latitude, float $longitude, int $timeZone, float $date, ?City $city, string $format): array
    {
        $prayTime = new CalculationPrayerTime;
        $prayTime->setCalcMethod(8);
        $prayTime->setDhuhrMinutes(2);
        $prayTime->setMaghribMinutes(2);

        $times     = $prayTime->getPrayerTimes($date, $latitude, $longitude, $timeZone);
        $subuh     = $this->normalizeTime($times[0]);
        $imsak     = $subuh->clone()->subMinutes(10);
        $terbit    = $this->normalizeTime($times[1]);
        $dhuhaDiff = $terbit->clone()->diffInMinutes($times[0]);
        $dhuha     = $terbit->clone()->addMinutes($dhuhaDiff / 3);

        if ($format == 'database') {
            return [
                'city_external_id' => $city->external_id ?? null,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $imsak,
                'subuh'            => $this->normalizeTime($times[0]),
                'terbit'           => $terbit,
                'dhuha'            => $dhuha,
                'dzuhur'           => $this->normalizeTime($times[2]),
                'ashar'            => $this->normalizeTime($times[3]),
                'maghrib'          => $this->normalizeTime($times[5]),
                'isya'             => $this->normalizeTime($times[6]),
            ];
        } elseif ($format == 'longlat') {
            return [
                'latitude'  => (string) $latitude,
                'longitude' => (string) $longitude,
                'prayer_at' => $this->normalizeDate($date),
                'imsak'     => $imsak,
                'subuh'     => $this->normalizeTime($times[0]),
                'terbit'    => $terbit,
                'dhuha'     => $dhuha,
                'dzuhur'    => $this->normalizeTime($times[2]),
                'ashar'     => $this->normalizeTime($times[3]),
                'maghrib'   => $this->normalizeTime($times[5]),
                'isya'      => $this->normalizeTime($times[6]),
            ];

        } elseif ($format == 'city') {
            return [
                'city_external_id' => $city->external_id ?? null,
                'city_name'        => $city->name,
                'latitude'         => $city->latitude,
                'longitude'        => $city->longitude,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $imsak,
                'subuh'            => $this->normalizeTime($times[0]),
                'terbit'           => $terbit,
                'dhuha'            => $dhuha,
                'dzuhur'           => $this->normalizeTime($times[2]),
                'ashar'            => $this->normalizeTime($times[3]),
                'maghrib'          => $this->normalizeTime($times[5]),
                'isya'             => $this->normalizeTime($times[6]),
            ];

        }

        return [];
    }

    protected function calculateUsingThirdPartyLibForSingleDate(float $latitude, float $longitude, int $timeZone, float $date, ?City $city, string $format): array
    {
        $prayer = new Prayer;
        $prayer->setCoordinates($longitude, $latitude);
        $prayer->setMethod('singapore');
        $prayer->setAdjustments(
            fajr: 0, sunrise: 0, duhr: -1, asr: 0, maghrib: 0, isha: 0
        );

        $times = $prayer->times($date);
        $times->setTimeZone($timeZone);

        $dhuhaDiff = $times->sunrise->clone()->diffInMinutes($times->fajr);
        $dhuha     = $times->sunrise->clone()->addMinutes($dhuhaDiff / 3);
        if ($format == 'database') {
            return [
                'city_external_id' => $city->external_id ?? null,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $times->fajr->clone()->subMinutes(10),
                'subuh'            => $times->fajr,
                'terbit'           => $times->sunrise,
                'dhuha'            => $dhuha,
                'dzuhur'           => $times->duhr,
                'ashar'            => $times->asr,
                'maghrib'          => $times->maghrib,
                'isya'             => $times->isha,
            ];

        } elseif ($format == 'longlat') {
            return [
                'latitude'  => (string) $latitude,
                'longitude' => (string) $longitude,
                'prayer_at' => $this->normalizeDate($date),
                'imsak'     => $times->fajr->clone()->subMinutes(10),
                'subuh'     => $times->fajr,
                'terbit'    => $times->sunrise,
                'dhuha'     => $dhuha,
                'dzuhur'    => $times->duhr,
                'ashar'     => $times->asr,
                'maghrib'   => $times->maghrib,
                'isya'      => $times->isha,
            ];

        } elseif ($format == 'city') {
            return [
                'city_external_id' => $city->external_id,
                'city_name'        => $city->name,
                'latitude'         => $city->latitude,
                'longitude'        => $city->longitude,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $times->fajr->clone()->subMinutes(10),
                'subuh'            => $times->fajr,
                'terbit'           => $times->sunrise,
                'dhuha'            => $dhuha,
                'dzuhur'           => $times->duhr,
                'ashar'            => $times->asr,
                'maghrib'          => $times->maghrib,
                'isya'             => $times->isha,
            ];

        }

        return [];
    }
}
