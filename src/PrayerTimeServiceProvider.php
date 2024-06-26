<?php

namespace Jhonoryza\LaravelPrayertime;

use Illuminate\Support\ServiceProvider;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerProvinceCity;
use Jhonoryza\LaravelPrayertime\Console\Commands\SyncPrayerTimes;
use Jhonoryza\LaravelPrayertime\Support\Concerns\Interface\PrayerTime;
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
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
        $this->mergeConfigFrom(__DIR__ . '/../config/prayertime.php', 'prayertime');

        $source = config('prayertime.source');
        $this->app->bind(PrayerTime::class, function () use ($source) {
            $class = match ($source) {
                'kemenag'            => KemenagPrayerTime::class,
                'myquran.com'        => MyQuranPrayerTime::class,
                'manual calculation' => ManualPrayerTime::class,
                default              => config('prayertime.custom_prayer_time_class'),
            };

            return new $class;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/prayertime.php' => config_path('prayertime.php'),
        ], 'prayertime-config');
    }
}
