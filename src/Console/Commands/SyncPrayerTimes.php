<?php

namespace Jhonoryza\LaravelPrayertime\Console\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jhonoryza\LaravelPrayertime\Models\City;
use Jhonoryza\LaravelPrayertime\Models\Prayertime;
use Jhonoryza\LaravelPrayertime\Models\Province;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime as PrayerTimeInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class SyncPrayerTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pray:sync-times {--Y|year=2024}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync prayer times data';

    /**
     * Execute the console command.
     */
    public function handle(PrayerTimeInterface $prayerTime): int
    {
        [
            'year' => $year,
            'provinceName' => $provinceName,
            'cityName' => $cityName,
        ] = $this->getPreferences();

        $this->info('Start sync year '.$year);

        $cities = City::query()
            ->when(
                $provinceName !== null,
                fn ($query) => $query->whereRelation('province', 'name', $provinceName),
            )
            ->when(
                $cityName !== null,
                fn ($query) => $query->where('name', $cityName),
            )
            ->get();

        if ($cities->isEmpty()) {
            $this->warn('No cities found');

            return 0;
        }

        $this->info('Syncing data...');

        foreach ($cities as $city) {

            // fetch schedules
            $schedules = $this->fetchPrayerTime($prayerTime, $city, $year);

            // upsert schedules to database for specific city
            if ($schedules->isNotEmpty()) {
                DB::transaction(function () use ($schedules, $city) {
                    Prayertime::query()
                        ->upsert(
                            $schedules->toArray(),
                            ['city_external_id', 'prayer_at'],
                            ['imsak', 'subuh', 'terbit', 'dhuha', 'dzuhur', 'ashar', 'maghrib', 'isya']
                        );
                    $this->info('generated data for '.$city->name);
                });
            }

        }

        $this->info('Done!');

        return 1;
    }

    protected function fetchPrayerTime(PrayerTimeInterface $prayerTime, City $city, int $year): Collection
    {
        $normalizedSchedules = collect();
        for ($month = 1; $month <= 12; $month++) {
            try {
                $schedules = $prayerTime->getPrayerTimes(
                    $city->province_external_id, $city->external_id, $month, $year
                );

                if (empty($schedules)) {
                    $this->warn('No schedules found for city '.$city->name.' month '.$month);

                    continue;
                }

                foreach ($schedules as $schedule) {
                    $normalizedSchedules->add($schedule);
                }

                $this->info('collect data for '.$city->name.' month '.$month);
            } catch (GuzzleException $e) {
                $this->warn('skipping city '.$city->name.' month '.$month);
                $this->error($e->getMessage());

                continue;
            }
        }

        return $normalizedSchedules;
    }

    protected function getPreferences(): array
    {
        $year = text(
            label: 'What year to sync?',
            default: $this->option('year'),
            required: true
        );

        $chooseProvince = confirm(
            label: 'want to choose specific province ?',
            default: false
        );

        $provinceName = ! $chooseProvince ?
            null
            : search(
                label: 'Choose province',
                options: fn ($search) => Province::query()
                    ->where('name', 'like', '%'.$search.'%')
                    ->pluck('name')
                    ->toArray(),
            );

        if ($provinceName != null) {
            $this->info('Province selected: '.$provinceName);
        }

        $chooseCity = confirm(
            label: 'want to choose specific city ?',
            default: false
        );

        $cityName = ! $chooseCity ?
            null
            : search(
                label: 'Choose city',
                options: fn ($search) => City::query()
                    ->when(
                        $provinceName !== null,
                        fn ($query) => $query->whereRelation('province', 'name', $provinceName),
                    )
                    ->where('name', 'like', '%'.$search.'%')
                    ->pluck('name')
                    ->toArray(),
            );

        if ($cityName != null) {
            $this->info('City selected: '.$cityName);
        }

        return [
            'year' => $year,
            'provinceName' => $provinceName,
            'cityName' => $cityName,
        ];
    }
}
