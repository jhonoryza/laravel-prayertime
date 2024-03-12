<?php

namespace Jhonoryza\LaravelPrayertime;

use Illuminate\Support\ServiceProvider;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerProvinceCity;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerTimes;
use Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime;
use Jhonoryza\LaravelPrayertime\Support\KemenagPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\MyQuranPrayerTime;

class PrayerTimeServiceProvider extends ServiceProvider
{
    /**
     * @throws \Exception
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPrayerProvinceCity::class,
                SyncPrayerTimes::class,
            ]);
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
        $this->mergeConfigFrom(__DIR__ . '/../config/prayertime.php', 'prayertime');

        $source = config('prayertime.source');
        if ($source == 'kemenag') {
            $this->app->bind(PrayerTime::class, KemenagPrayerTime::class);
        } elseif ($source == 'myquran.com') {
            $this->app->bind(PrayerTime::class, MyQuranPrayerTime::class);
        } elseif ($source == 'manual calculation') {
            throw new \Exception('manual calculation not implemented yet');
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/prayertime.php' => config_path('prayertime.php'),
        ], 'prayertime-config');
    }
}
