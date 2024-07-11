<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits\ProvinceCityTrait;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits\SupportsTrait;

class KemenagPrayerTime implements PrayerTime
{
    use ProvinceCityTrait;
    use SupportsTrait;

    public function getFromLongLatOnSpecificYear($latitude, $longitude, $year)
    {
        return [];
    }

    public function getFromLongLatOnSpecificDate($latitude, $longitude, $date)
    {
        return [];
    }

    public function getFromCityIdOnSpecificYear($cityId, $year)
    {
        return [];
    }

    public function getFromCityIdOnSpecificDate($cityId, $date)
    {
        return [];
    }

    /**
     * @throws GuzzleException
     */
    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
    {
        $response = config('prayertime.kemenag_source') == 'api' ?
            $this->apiJadwalShalat($provinceId, $cityId, $month, $year)
            : $this->crawlerJadwalShalat($provinceId, $cityId, $month, $year);

        $normalizedSchedules = collect();
        collect($response['data'])->each(function (array $schedule, string $date) use ($cityId, $normalizedSchedules) {
            $normalizedSchedules->add([
                'city_external_id' => $cityId,
                'prayer_at' => $this->normalizeDate($date),
                'imsak' => $this->normalizeTime($schedule['imsak']),
                'subuh' => $this->normalizeTime($schedule['subuh']),
                'terbit' => $this->normalizeTime($schedule['terbit']),
                'dhuha' => $this->normalizeTime($schedule['dhuha']),
                'dzuhur' => $this->normalizeTime($schedule['dzuhur']),
                'ashar' => $this->normalizeTime($schedule['ashar']),
                'maghrib' => $this->normalizeTime($schedule['maghrib']),
                'isya' => $this->normalizeTime($schedule['isya']),
            ]);
        });

        return $normalizedSchedules->toArray();
    }

    private function apiJadwalShalat(string $provinceId, string $cityId, int $month, int $year): array
    {
        return Http::baseUrl($this->getBaseUrl())
            ->asForm()
            ->post('apiv1/getShalatJadwal', [
                'param_token' => config('prayertime.kemenag_api_key'),
                'param_prov' => $provinceId,
                'param_kabko' => $cityId,
                'param_bln' => $month,
                'param_thn' => $year,
            ])->json();
    }

    private function crawlerJadwalShalat(string $provinceId, string $cityId, int $month, int $year): array
    {
        $cookies = $this->getCookies();

        return Http::baseUrl($this->getBaseUrl())
            ->asForm()
            ->withOptions(['cookies' => $cookies])
            ->post('/ajax/getShalatbln', [
                'x' => $provinceId,
                'y' => $cityId,
                'bln' => $month,
                'thn' => $year,
            ])->json();
    }
}
