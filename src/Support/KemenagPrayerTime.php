<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime;
use Symfony\Component\DomCrawler\Crawler;

class KemenagPrayerTime implements PrayerTime
{
    /**
     * @throws GuzzleException
     */
    public function getProvinces(): array
    {
        $client = new Client([
            'base_uri' => $this->getBaseUrl(),
        ]);

        $response = $client->get('/jadwalshalat', [
            'cookies' => $this->getCookies(),
        ]);

        $provinces = [];

        (new Crawler($response->getBody()->getContents()))
            ->filter('#search_prov option')
            ->each(function (Crawler $node) use (&$provinces) {
                if ($node->text() != 'PUSAT') {
                    $provinces[] = [
                        'value' => $node->attr('value'),
                        'text'  => $node->text(),
                    ];
                }
            });

        return $provinces;
    }

    /**
     * @throws GuzzleException
     */
    public function getCities(string $provinceId): array
    {
        $client = new Client([
            'base_uri' => $this->getBaseUrl(),
        ]);

        $response = $client->post('/ajax/getKabkoshalat', [
            'cookies'     => $this->getCookies(),
            'form_params' => [
                'x' => $provinceId,
            ],
        ]);

        $cities = [];

        (new Crawler($response->getBody()->getContents()))
            ->filter('option')
            ->each(function (Crawler $node) use (&$cities) {
                $cities[] = [
                    'value' => $node->attr('value'),
                    'text'  => $node->text(),
                ];
            });

        return $cities;
    }

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

    /**
     * @throws GuzzleException
     */
    protected function getCookies(): CookieJar
    {
        $cookieJar = new CookieJar;

        $client = new Client([
            'base_uri' => $this->getBaseUrl(),
            'cookies'  => $cookieJar,
        ]);

        $client->get('jadwalshalat');

        return $cookieJar;
    }

    public function getBaseUrl(): string
    {
        return config('prayertime.base_uri');
    }

    protected function normalizeDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    protected function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }
}
