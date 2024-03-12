<?php

namespace Jhonoryza\LaravelPrayertime;

use Illuminate\Support\ServiceProvider;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerProvinceCity;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerTimes;
use Jhonoryza\LaravelPrayertime\Support\Concerns\PrayerTime;
use Jhonoryza\LaravelPrayertime\Support\KemenagPrayerTime;
use Jhonoryza\LaravelPrayertime\Support\ManualPrayerTime;
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
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
        $this->mergeConfigFrom(__DIR__.'/../config/prayertime.php', 'prayertime');

        $source = config('prayertime.source');
        $this->app->bind(PrayerTime::class, function () use ($source) {
            if ($source == 'kemenag') {
                return new KemenagPrayerTime();
            } elseif ($source == 'myquran.com') {
                return new MyQuranPrayerTime();
            } elseif ($source == 'manual calculation') {
                return new ManualPrayerTime();
            }

            $class = config('prayertime.custom_prayer_time_class');

            return new $class();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/prayertime.php' => config_path('prayertime.php'),
        ], 'prayertime-config');
    }
}
