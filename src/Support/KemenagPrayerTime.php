<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits\ProvinceCityTrait;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits\SupportsTrait;

class KemenagPrayerTime implements PrayerTime
{
    use ProvinceCityTrait;
    use SupportsTrait;

    /**
     * @throws GuzzleException
     */
    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
    {
        $client = new Client([
            'base_uri' => $this->getBaseUrl(),
        ]);

        $response = $client->post('/ajax/getShalatbln', [
            'cookies'     => $this->getCookies(),
            'form_params' => [
                'x'   => $provinceId,
                'y'   => $cityId,
                'bln' => $month,
                'thn' => $year,
            ],
        ]);

        $schedules = json_decode($response->getBody()->getContents(), true);

        $normalizedSchedules = collect();
        collect($schedules['data'])->each(function (array $schedule, string $date) use ($cityId, $normalizedSchedules) {
            $normalizedSchedules->add([
                'city_external_id' => $cityId,
                'prayer_at'        => $this->normalizeDate($date),
                'imsak'            => $this->normalizeTime($schedule['imsak']),
                'subuh'            => $this->normalizeTime($schedule['subuh']),
                'terbit'           => $this->normalizeTime($schedule['terbit']),
                'dhuha'            => $this->normalizeTime($schedule['dhuha']),
                'dzuhur'           => $this->normalizeTime($schedule['dzuhur']),
                'ashar'            => $this->normalizeTime($schedule['ashar']),
                'maghrib'          => $this->normalizeTime($schedule['maghrib']),
                'isya'             => $this->normalizeTime($schedule['isya']),
            ]);
        });

        return $normalizedSchedules->toArray();
    }
}
