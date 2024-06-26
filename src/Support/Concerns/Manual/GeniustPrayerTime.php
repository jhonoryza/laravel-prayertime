<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual;

use GeniusTS\PrayerTimes\Prayer;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\SupportsTrait;

class GeniustPrayerTime
{
    use SupportsTrait;

    public function calculate(float $latitude, float $longitude, int $timeZone, float $date, float $endDate, ?City $city, string $format): array
    {
        $prayer = new Prayer;
        $prayer->setCoordinates($longitude, $latitude);
        $prayer->setMethod('singapore');
        $prayer->setAdjustments(
            fajr: 0, sunrise: 0, duhr: 0, asr: 0, maghrib: 0, isha: 0
        );
        $prayTimes = [];

        while ($date < $endDate) {
            $times = $prayer->times($date);
            $times->setTimeZone($timeZone);

            $dhuhaDiff = $times->sunrise->clone()->diffInMinutes($times->fajr);
            $dhuha     = $times->sunrise->clone()->addMinutes($dhuhaDiff / 3);
            if ($format == 'database') {
                $prayTimes[] = [
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
                $prayTimes[] = [
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
                $prayTimes[] = [
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

            $date += 24 * 60 * 60;  // next day
        }

        return $prayTimes;
    }

    public function calculateForSingleDate(float $latitude, float $longitude, int $timeZone, float $date, ?City $city, string $format): array
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
