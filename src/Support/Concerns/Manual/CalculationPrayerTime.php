<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual;

use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits\SupportsTrait;

class CalculationPrayerTime extends PrayTime
{
    use SupportsTrait;

    public $kemenag = 8;    // Indonesia - Kemenag

    public function PrayTime($methodID = 0)
    {
        /*  var $methodParams[methodNum] = array(fa, ms, mv, is, iv);

               fa : fajr angle
               ms : maghrib selector (0 = angle; 1 = minutes after sunset)
               mv : maghrib parameter value (in angle or minutes)
               is : isha selector (0 = angle; 1 = minutes after maghrib)
               iv : isha parameter value (in angle or minutes)
       */
        $this->methodParams[$this->kemenag] = [20, 1, 0, 0, 18];

        parent::PrayTime($methodID);
    }

    public function calculate(float $latitude, float $longitude, int $timeZone, float $date, float $endDate, ?City $city, string $format): array
    {
        $prayTime = new self;
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

    public function calculateForSingleDate(float $latitude, float $longitude, int $timeZone, float $date, ?City $city, string $format): array
    {
        $prayTime = new self;
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
}
