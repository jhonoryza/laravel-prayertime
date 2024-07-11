<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

trait SupportsTrait
{
    /**
     * @throws GuzzleException
     */
    public function getCookies(): CookieJar
    {
        $cookieJar = new CookieJar;

        $cookies = Http::baseUrl($this->getBaseUrl())
            ->withOptions([
                'cookies' => $cookieJar,
            ], 'bimasislam.kemenag.go.id')
            ->get('jadwalshalat')
            ->cookies();
        foreach ($cookies as $cookie) {
            $cookieJar->setCookie($cookie);
        }

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
