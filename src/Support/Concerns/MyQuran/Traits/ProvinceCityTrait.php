<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\MyQuran\Traits;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

trait ProvinceCityTrait
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
}
