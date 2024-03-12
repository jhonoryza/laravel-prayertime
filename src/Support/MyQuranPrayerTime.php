<?php

namespace Jhonoryza\LaravelPrayertime\Support;

use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime;

class MyQuranPrayerTime implements PrayerTime
{
    public function getProvinces(): array
    {
        return [
            [
                'value' => 'all',
                'text'  => 'Semua Provinsi',
            ],
        ];
    }

    /**
     * @throws RequestException
     */
    public function getCities(string $provinceId): array
    {
        unset($provinceId);
        $items = Http::timeout(10)
            ->connectTimeout(10)
            ->baseUrl($this->getBaseUrl())
            ->acceptJson()
            ->get('sholat/kota/semua')
            ->throw()
            ->json();

        $cities = [];
        foreach ($items['data'] ?? [] as $item) {
            $cities[] = [
                'value' => $item['id'],
                'text'  => $item['lokasi'],
            ];
        }

        return $cities;
    }

    /**
     * @throws RequestException
     */
    public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
    {
        unset($provinceId);
        $items = Http::timeout(10)
            ->connectTimeout(10)
            ->baseUrl($this->getBaseUrl())
            ->acceptJson()
            ->get("sholat/jadwal/$cityId/$year/$month")
            ->throw()
            ->json();
        if (! isset($items['data']) || $items['data']['id'] != $cityId || ! isset($items['data']['jadwal'])) {
            return [];
        }
        $prayerTimes = [];
        foreach ($items['data']['jadwal'] as $item) {
            $prayerTimes[] = [
                'city_external_id' => $cityId,
                'prayer_at'        => $this->normalizeDate($item['date']),
                'imsak'            => $this->normalizeTime($item['imsak']),
                'subuh'            => $this->normalizeTime($item['subuh']),
                'terbit'           => $this->normalizeTime($item['terbit']),
                'dhuha'            => $this->normalizeTime($item['dhuha']),
                'dzuhur'           => $this->normalizeTime($item['dzuhur']),
                'ashar'            => $this->normalizeTime($item['ashar']),
                'maghrib'          => $this->normalizeTime($item['maghrib']),
                'isya'             => $this->normalizeTime($item['isya']),
            ];
        }

        return $prayerTimes;
    }

    protected function normalizeDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    protected function normalizeTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }

    public function getBaseUrl(): string
    {
        return config('prayertime.base_uri');
    }
}
