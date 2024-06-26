<?php

namespace Jhonoryza\LaravelPrayertime\Console\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Jhonoryza\LaravelPrayertime\Models\Province;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;

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
        $this->info('Start');
        $this->syncProvince($prayerTime);
        $this->syncCity($prayerTime);
        $this->info('Done');

        return 1;
    }

    protected function syncProvince(PrayerTime $prayerTime): void
    {
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
            Province::query()
                ->firstOrCreate([
                    'external_id' => $province['value'],
                ], [
                    'name'      => $province['text'],
                    'latitude'  => $province['latitude']  ?? 0,
                    'longitude' => $province['longitude'] ?? 0,
                ]);
            $this->info('synced ' . $province['text']);
        }
    }

    protected function syncCity(PrayerTime $prayerTime): void
    {
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
                $province->cities()
                    ->firstOrCreate([
                        'external_id' => $city['value'],
                    ], [
                        'name'      => $city['text'],
                        'latitude'  => $city['latitude']  ?? 0,
                        'longitude' => $city['longitude'] ?? 0,
                    ]);
                $this->info('synced ' . $city['text']);
            }
        }
    }
}
