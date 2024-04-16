<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use GeniusTS\PrayerTimes\Prayer;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\CalculationPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\ManualAdditionTrait;

class ManualPrayerTime implements \Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime
{
    use ManualAdditionTrait;

    public function getBaseUrl(): string
    {
        return '';
    }

    public function getProvinces(): array
    {
        $json  = file_get_contents(__DIR__ . '/../../public/json/manual-calc/provinces.json');
        $items = json_decode($json, true);

        $provinces = [];
        foreach ($items as $item) {
            $provinces[] = [
                'value'     => $item['id'],
                'text'      => $item['name'],
                'latitude'  => $item['latitude'],
                'longitude' => $item['longitude'],
            ];
        }

        return $provinces;
    }

    public function getCities(string $provinceId): array
    {
        $json  = file_get_contents(__DIR__ . '/../../public/json/manual-calc/cities.json');
        $items = json_decode($json, true);

        $cities = [];
        collect($items)->filter(function ($item) use ($provinceId) {
            return $item['province_id'] === $provinceId;
        })->each(function ($item) use (&$cities) {
            $cities[] = [
                'value'     => $item['id'],
                'text'      => $item['name'],
                'latitude'  => $item['latitude'],
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

        return config('prayertime.use_package_geniusts_prayer_times') ?
            $this->calculateUsingThirdPartyLib(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'database'
            ) : $this->calculateManual(
                $latitude,
                $longitude,
                $timeZone,
                $date,
                $endDate,
                $city,
                'database'
            );

    }

    protected function calculateManual(float $latitude, float $longitude, int $timeZone, float $date, float $endDate, ?City $city, string $format): array
    {
        $prayTime = new CalculationPrayerTime;
        $prayTime->setCalcMethod(8);
        $prayTime->setDhuhrMinutes(2);
        $prayTime->setMaghribMinutes(2);

        $prayTimes = [];

        while ($date < $endDate) {
            $times     = $prayTime->getPrayerTimes($date, $latitude, $longitude, $timeZone);
            $subuh     = $this->normalizeTime($times[0]);
            $imsak     = $subuh->clone()->subMinutes(10);
            $terbit    = $this->normalizeTime($times[1]);
            $dhuhaDiff = $terbit->clone()->diffInMinutes($times[0]);
            $dhuha     = $terbit->clone()->addMinutes($dhuhaDiff / 3);

            if ($format == 'database') {
                $prayTimes[] = [
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
                $prayTimes[] = [
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
                $prayTimes[] = [
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

            $date += 24 * 60 * 60;  // next day
        }

        return $prayTimes;
    }

    protected function calculateUsingThirdPartyLib(float $latitude, float $longitude, int $timeZone, float $date, float $endDate, ?City $city, string $format): array
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
    protected function getTimezone(float $cur_lat, float $cur_long): float
    {
        $timezone_ids = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'ID');

        $time_zone   = 0;
        $tz_distance = 0;

        foreach ($timezone_ids as $timezone_id) {
            $timezone = new DateTimeZone($timezone_id);
            $location = $timezone->getLocation();
            $tz_lat   = $location['latitude'];
            $tz_long  = $location['longitude'];

            $theta    = $cur_long - $tz_long;
            $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
            $distance = acos($distance);
            $distance = abs(rad2deg($distance));

            if (! $time_zone || $tz_distance > $distance) {
                $time_zone   = $timezone;
                $tz_distance = $distance;
            }
        }

        $datetime = new DateTime('now', $time_zone);

        return $time_zone->getOffset($datetime) / 3600;
    }
}
