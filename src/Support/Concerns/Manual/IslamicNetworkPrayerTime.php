<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual;

use IslamicNetwork\PrayerTimes\Method;
use IslamicNetwork\PrayerTimes\PrayerTimes;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\SupportsTrait;

class IslamicNetworkPrayerTime
{
    use SupportsTrait;

    public function calculate(float $latitude, float $longitude, int $timeZone, float $date, float $endDate, ?City $city, string $format): array
    {
        $prayer = new PrayerTimes(Method::METHOD_KEMENAG);

        $prayTimes = [];

        while ($date < $endDate) {
            $prayTimes[] = $this->getTimes($prayer, $latitude, $longitude, $timeZone, $date, $format, $city);
            $date += 24 * 60 * 60;  // next day
        }

        return $prayTimes;
    }

    public function calculateForSingleDate(float $latitude, float $longitude, int $timeZone, float $date, ?City $city, string $format): array
    {
        $prayer = new PrayerTimes(Method::METHOD_KEMENAG);

        return $this->getTimes($prayer, $latitude, $longitude, $timeZone, $date, $format, $city);
    }

    private function getTimes($prayer, $latitude, $longitude, $timeZone, $date, $format, ?City $city): array
    {
        $dateTime = $this->floatToDateTime($date, $this->floatToTimezoneString($timeZone));
        $times    = $prayer->getTimes($dateTime, $latitude, $longitude);

        $subuh     = $this->normalizeTime($times['Fajr']);
        $imsak     = $this->normalizeTime($times['Imsak']);
        $terbit    = $this->normalizeTime($times['Sunrise']);
        $dhuhaDiff = $terbit->clone()->diffInMinutes($times['Fajr']);
        $dhuha     = $terbit->clone()->addMinutes($dhuhaDiff / 3);

        if ($format == 'database') {
            return [
                'city_external_id' => $city->external_id ?? null,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $imsak,
                'subuh'            => $subuh,
                'terbit'           => $terbit,
                'dhuha'            => $dhuha,
                'dzuhur'           => $this->normalizeTime($times['Dhuhr']),
                'ashar'            => $this->normalizeTime($times['Asr']),
                'maghrib'          => $this->normalizeTime($times['Maghrib']),
                'isya'             => $this->normalizeTime($times['Isha']),
            ];
        } elseif ($format == 'longlat') {
            return [
                'latitude'  => (string) $latitude,
                'longitude' => (string) $longitude,
                'prayer_at' => $this->normalizeDate($date),
                'imsak'     => $imsak,
                'subuh'     => $subuh,
                'terbit'    => $terbit,
                'dhuha'     => $dhuha,
                'dzuhur'    => $this->normalizeTime($times['Dhuhr']),
                'ashar'     => $this->normalizeTime($times['Asr']),
                'maghrib'   => $this->normalizeTime($times['Maghrib']),
                'isya'      => $this->normalizeTime($times['Isha']),
            ];

        } elseif ($format == 'city') {
            return [
                'city_external_id' => $city->external_id ?? null,
                'city_name'        => $city->name,
                'latitude'         => $city->latitude,
                'longitude'        => $city->longitude,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $imsak,
                'subuh'            => $subuh,
                'terbit'           => $terbit,
                'dhuha'            => $dhuha,
                'dzuhur'           => $this->normalizeTime($times['Dhuhr']),
                'ashar'            => $this->normalizeTime($times['Asr']),
                'maghrib'          => $this->normalizeTime($times['Maghrib']),
                'isya'             => $this->normalizeTime($times['Isha']),
            ];

        }

        return [];
    }
}
