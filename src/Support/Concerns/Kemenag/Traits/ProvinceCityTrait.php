<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns\Kemenag\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

trait ProvinceCityTrait
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
}
