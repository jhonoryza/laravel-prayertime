<?php

namespace Jhonoryza\LaravelPrayertime\Console\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Models\Province;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;

use function Laravel\Prompts\confirm;

class SyncPrayerProvinceCity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pray:sync-city';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync prayer province city';

    /**
     * Execute the console command.
     */
    public function handle(PrayerTime $prayerTime): int
    {
        if (confirm('Want to truncate city table?')) {
            City::truncate();
        }
        if (confirm('Want to truncate province table?')) {
            Province::truncate();
        }
        $this->info('Start');
        if (confirm('Want to sync province data?')) {
            $this->syncProvince($prayerTime);

        }
        if (confirm('Want to sync city data?')) {
            $this->syncCity($prayerTime);
        }
        $this->info('Done');

        return 1;
    }

    protected function syncProvince(PrayerTime $prayerTime): void
    {
        $json          = file_get_contents(__DIR__ . '/../../../public/json/manual-calc/provinces.json');
        $provinceItems = collect(json_decode($json, true));

        // get provinces
        try {
            $provinces = $prayerTime->getProvinces();
        } catch (GuzzleException $e) {
            $this->warn('error get provinces');
            $this->error($e->getMessage());

            return;
        }

        $this->info('Syncing Province data...');

        foreach ($provinces as $province) {
            $item = $provinceItems->firstWhere('name', $province['text']);
            Province::query()
                ->firstOrCreate([
                    'external_id' => $province['value'],
                ], [
                    'name'      => $province['text'],
                    'latitude'  => $province['latitude']  ?? $item['latitude'] ?? 0,
                    'longitude' => $province['longitude'] ?? $item['longitude'] ?? 0,
                ]);
            $this->info('synced ' . $province['text']);
        }
    }

    protected function syncCity(PrayerTime $prayerTime): void
    {
        $json      = file_get_contents(__DIR__ . '/../../../public/json/manual-calc/cities.json');
        $cityItems = collect(json_decode($json, true));

        $this->info('Syncing City data...');
        $provinces = Province::query()->get();
        foreach ($provinces as $province) {

            // get cities
            try {
                $cities = $prayerTime->getCities($province->external_id);
            } catch (GuzzleException $e) {
                $this->warn('skip ' . $province->name);
                $this->error($e->getMessage());

                continue;
            }

            // generate cities from province
            foreach ($cities as $city) {
                $item = $cityItems->firstWhere('name', str_replace('KAB.', 'KABUPATEN', $city['text']));
                $province->cities()
                    ->firstOrCreate([
                        'external_id' => $city['value'],
                    ], [
                        'name'      => $city['text'],
                        'latitude'  => $city['latitude']  ?? $item['latitude'] ?? 0,
                        'longitude' => $city['longitude'] ?? $item['longitude'] ?? 0,
                    ]);
                $this->info('synced ' . $city['text']);
            }
        }
    }
}
