<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\CalculationPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\GeniustPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\IslamicNetworkPrayerTime;

trait SupportsTrait
{
    public function normalizeDate(float $date): string
    {
        return date('Y-m-d H:i:s', $date);
    }

    public function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }

    /**
     * @throws \Exception
     */
    public function getTimezone(float $cur_lat, float $cur_long): float
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

    public function floatToDateTime(float $floatDate, string $timezone): DateTime
    {
        // Pisahkan bagian utuh dan bagian desimal
        $integerPart = (int) $floatDate;
        $decimalPart = $floatDate - $integerPart;

        // Konversi bagian utuh menjadi objek DateTime
        $dateTime = new DateTime("@$integerPart");
        $dateTime->setTimezone(new DateTimeZone($timezone));

        // Tambahkan bagian desimal sebagai mikrodetik
        $microseconds = (int) ($decimalPart * 1000000);

        return $dateTime->modify("+$microseconds microseconds");
    }

    public function floatToTimezoneString($offset)
    {
        $timezoneList = DateTimeZone::listIdentifiers();
        foreach ($timezoneList as $timezone) {
            $dateTimeZone   = new DateTimeZone($timezone);
            $dateTime       = new DateTime('now', $dateTimeZone);
            $timezoneOffset = $dateTimeZone->getOffset($dateTime) / 3600; // Konversi detik ke jam

            // Jika offset cocok, kembalikan nama zona waktu
            if ($timezoneOffset == $offset) {
                return $timezone;
            }
        }

        return 'UTC'; // Default jika tidak ada kecocokan
    }

    public function getService(): CalculationPrayerTime|GeniustPrayerTime|IslamicNetworkPrayerTime
    {
        $source  = config('prayertime.manual_source');
        $service = match ($source) {
            'praytimes.org'                => (new CalculationPrayerTime),
            'geniusts/prayer-times'        => (new GeniustPrayerTime),
            'islamic-network/prayer-times' => (new IslamicNetworkPrayerTime),
            default                        => null,
        };

        if ($service == null) {
            throw new \Exception('Service not found');
        }

        return $service;
    }
}
