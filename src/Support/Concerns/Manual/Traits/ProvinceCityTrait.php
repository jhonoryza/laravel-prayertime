<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Manual\Traits;

trait ProvinceCityTrait
{
    public function getProvinces(): array
    {
        $json  = file_get_contents(__DIR__ . '/../../../../../public/json/manual-calc/provinces.json');
        $items = json_decode($json, true);

        $provinces = [];
        foreach ($items as $item) {
            $provinces[] = [
                'value'     => $item['id'],
                'text'      => $item['name'],
                'latitude'  => $item['latitude'],
                'longitude' => $item['longitude'],
            ];
        }

        return $provinces;
    }

    public function getCities(string $provinceId): array
    {
        $json  = file_get_contents(__DIR__ . '/../../../../../public/json/manual-calc/cities.json');
        $items = json_decode($json, true);

        $cities = [];
        collect($items)->filter(function ($item) use ($provinceId) {
            return $item['province_id'] === $provinceId;
        })->each(function ($item) use (&$cities) {
            $cities[] = [
                'value'     => $item['id'],
                'text'      => $item['name'],
                'latitude'  => $item['latitude'],
                'longitude' => $item['longitude'],
            ];
        });

        return $cities;
    }
}
