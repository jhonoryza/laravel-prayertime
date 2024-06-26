<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;

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
}
