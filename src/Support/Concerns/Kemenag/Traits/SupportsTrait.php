<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;

trait SupportsTrait
{
    /**
     * @throws GuzzleException
     */
    public function getCookies(): CookieJar
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

    public function normalizeDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    public function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }
}
