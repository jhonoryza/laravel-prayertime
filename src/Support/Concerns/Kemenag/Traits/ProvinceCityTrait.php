<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

trait ProvinceCityTrait
{
    /**
     * @throws GuzzleException
     */
    public function getProvinces(): array
    {
        return config('prayertime.kemenag_source') == 'api' ? $this->apiProvince() : $this->crawlerProvince();
    }

    private function apiProvince(): array
    {
        $data = Http::baseUrl($this->getBaseUrl())
            ->retry(5, function (int $attempt) {
                return $attempt * 1000;
            })
            ->get('apiv1/getShalatProv', [
                'param_token' => config('prayertime.kemenag_api_key'),
            ])
            ->json();
        $provinces = [];
        foreach ($data as $item) {
            $provinces[] = [
                'value' => $item['provKode'],
                'text' => $item['provNama'],
            ];
        }

        return $provinces;
    }

    private function crawlerProvince(): array
    {
        $cookies = $this->getCookies();
        $response = Http::baseUrl($this->getBaseUrl())
            ->withOptions([
                'cookies' => $cookies,
            ])
            ->get('/jadwalshalat');

        $provinces = [];

        (new Crawler($response->body()))
            ->filter('#search_prov option')
            ->each(function (Crawler $node) use (&$provinces) {
                if ($node->text() != 'PUSAT') {
                    $provinces[] = [
                        'value' => $node->attr('value'),
                        'text' => $node->text(),
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
        return config('prayertime.kemenag_source') == 'api' ? $this->apiCity($provinceId) : $this->crawlerCity($provinceId);
    }

    private function apiCity(string $provinceId): array
    {
        $data = Http::baseUrl($this->getBaseUrl())
            ->retry(5, function (int $attempt) {
                return $attempt * 1000;
            })
            ->get('apiv1/getShalatKabko', [
                'param_token' => config('prayertime.kemenag_api_key'),
                'param_prov' => $provinceId,
            ])
            ->json();
        $cities = [];
        foreach ($data as $item) {
            $cities[] = [
                'value' => $item['kabkoKode'],
                'text' => $item['kabkoNama'],
            ];
        }

        return $cities;
    }

    private function crawlerCity(string $provinceId): array
    {
        $cookies = $this->getCookies();
        $response = Http::baseUrl($this->getBaseUrl())
            ->withOptions([
                'cookies' => $cookies,
            ])
            ->asForm()
            ->post('/ajax/getKabkoshalat', [
                'x' => $provinceId,
            ]);
        $cities = [];

        (new Crawler($response->body()))
            ->filter('option')
            ->each(function (Crawler $node) use (&$cities) {
                $cities[] = [
                    'value' => $node->attr('value'),
                    'text' => $node->text(),
                ];
            });

        return $cities;
    }
}
