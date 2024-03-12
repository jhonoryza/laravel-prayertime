<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use GeniusTS\PrayerTimes\Prayer;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\CalculationPrayerTime;

class ManualPrayerTime implements \Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime
{
    public function getBaseUrl(): string
    {
        return '';
    }

    public function getProvinces(): array
    {
        $json = file_get_contents(__DIR__.'/../../public/json/manual-calc/provinces.json');
        $items = json_decode($json, true);

        $provinces = [];
        foreach ($items as $item) {
            $provinces[] = [
                'value' => $item['id'],
                'text' => $item['name'],
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude'],
            ];
        }

        return $provinces;
    }

    public function getCities(string $provinceId): array
    {
        $json = file_get_contents(__DIR__.'/../../public/json/manual-calc/cities.json');
        $items = json_decode($json, true);

        $cities = [];
        collect($items)->filter(function ($item) use ($provinceId) {
            return $item['province_id'] === $provinceId;
        })->each(function ($item) use (&$cities) {
            $cities[] = [
                'value' => $item['id'],
                'text' => $item['name'],
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude'],
            ];
        });

        return $cities;
    }

    /**
     * @throws \Exception
     */
    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
    {
        $city = City::query()->where('external_id', $cityId)->first();
        if (! $city instanceof City) {
            return [];
        }
        $latitude = $city->latitude;
        $longitude = $city->longitude;
        $timeZone = $this->getTimezone($latitude, $longitude);

        $date = strtotime($year.'-1-1');
        $endDate = strtotime(($year + 1).'-1-1');

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateGenius(
                $latitude,
                $longitude,
                $timeZone,
                $cityId,
                $date,
                $endDate
            ) : $this->calculateManual(
                $latitude,
                $longitude,
                $timeZone,
                $cityId,
                $date,
                $endDate
            );
    }

    protected function calculateManual(int $latitude, int $longitude, int $timeZone, string $cityId, float $date, float $endDate): array
    {
        $prayTime = new CalculationPrayerTime();
        $prayTime->setCalcMethod(8);
        $prayTime->setDhuhrMinutes(2);
        $prayTime->setMaghribMinutes(2);

        $prayTimes = [];

        while ($date < $endDate) {
            $times = $prayTime->getPrayerTimes($date, $latitude, $longitude, $timeZone);
            $subuh = $this->normalizeTime($times[0]);
            $imsak = $subuh->clone()->subMinutes(10);
            $terbit = $this->normalizeTime($times[1]);
            $dhuhaDiff = $terbit->clone()->diffInMinutes($times->fajr);
            $dhuha = $terbit->clone()->addMinutes($dhuhaDiff / 3);
            $prayTimes[] = [
                'city_external_id' => $cityId,
                'prayer_at' => $this->normalizeDate($date),
                'imsak' => $imsak,
                'subuh' => $this->normalizeTime($times[0]),
                'terbit' => $terbit,
                'dhuha' => $dhuha,
                'dzuhur' => $this->normalizeTime($times[2]),
                'ashar' => $this->normalizeTime($times[3]),
                'maghrib' => $this->normalizeTime($times[5]),
                'isya' => $this->normalizeTime($times[6]),
            ];

            $date += 24 * 60 * 60;  // next day
        }

        return $prayTimes;
    }

    protected function calculateGenius(int $latitude, int $longitude, int $timeZone, string $cityId, float $date, float $endDate): array
    {
        $prayer = new Prayer();
        $prayer->setCoordinates($longitude, $latitude);
        $prayer->setMethod('singapore');
        $prayer->setAdjustments(
            fajr: -1, sunrise: -4, duhr: -1, asr: -1, maghrib: 0, isha: 1
        );
        $prayTimes = [];

        while ($date < $endDate) {
            $times = $prayer->times($date);
            $times->setTimeZone($timeZone);

            $dhuhaDiff = $times->sunrise->clone()->diffInMinutes($times->fajr);
            $dhuha = $times->sunrise->clone()->addMinutes($dhuhaDiff / 3);
            $prayTimes[] = [
                'city_external_id' => $cityId,
                'prayer_at' => $this->normalizeDate($date),
                'imsak' => $times->fajr->clone()->subMinutes(10)->format('H:i'),
                'subuh' => $times->fajr->format('H:i'),
                'terbit' => $times->sunrise->format('H:i'),
                'dhuha' => $dhuha,
                'dzuhur' => $times->duhr->format('H:i'),
                'ashar' => $times->asr->format('H:i'),
                'maghrib' => $times->maghrib->format('H:i'),
                'isya' => $times->isha->format('H:i'),
            ];

            $date += 24 * 60 * 60;  // next day
        }

        return $prayTimes;
    }

    protected function normalizeDate(float $date): string
    {
        return date('Y-m-d H:i:s', $date);
    }

    protected function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }

    /**
     * @throws \Exception
     */
    protected function getTimezone(int $cur_lat, int $cur_long): int
    {
        $timezone_ids = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'ID');

        $time_zone = 0;
        $tz_distance = 0;

        foreach ($timezone_ids as $timezone_id) {
            $timezone = new DateTimeZone($timezone_id);
            $location = $timezone->getLocation();
            $tz_lat = $location['latitude'];
            $tz_long = $location['longitude'];

            $theta = $cur_long - $tz_long;
            $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
            $distance = acos($distance);
            $distance = abs(rad2deg($distance));

            if (! $time_zone || $tz_distance > $distance) {
                $time_zone = $timezone;
                $tz_distance = $distance;
            }
        }

        $datetime = new DateTime('now', $time_zone);

        return $time_zone->getOffset($datetime) / 3600;
    }
}
