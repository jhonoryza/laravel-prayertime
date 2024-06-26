# Laravel Prayertime

## install

```bash
composer require jhonoryza/laravel-prayertime
```

run migration

```bash
php artisan migrate
```

this will create 3 tables: provinces, cities and prayertimes table

publish config file

```bash
php artisan vendor:publish --tag=prayertime-config
```

## sources

you can choose from one of this source by configuring config file `prayertime.php`

1. manual calculation : reference from prayertimes.org

2. crawling bimasislam kemenag website [https://bimasislam.kemenag.go.id/jadwalshalat](https://bimasislam.kemenag.go.id/jadwalshalat)

3. api from myquran.com (sebelumnya domain https://api.banghasan.com/) 

## sync predefined city and province data

get city data from the source and save it to the database

```bash
php artisan pray:sync-city
```

## sync prayer times

get prayer times from the source and save it to the database

```bash
php artisan pray:sync-times
```

## general usage

```php
public function getPrayerTimes(string $provinceId, string $cityId, int $month, int $year): array
```

example :

```php
<?php

Route::get('/time', function (PrayerTime $prayer) {
    $prayerTimes = $prayer->getPrayerTimes(
        '',
        '3204', // Kab Bandung external id
        6,
        2024
    );
    foreach ($prayerTimes as $index => $times) {
        foreach ($times as $key => $prayerTime) {
            if (in_array($key, ['city_external_id', 'prayer_at'])) {
                continue;
            }
            $times[$key] = $prayerTime->format('H:i');
        }

        $prayerTimes[$index] = $times;
    }

    return response()->json([
        'data' => $prayerTimes,
    ]);
});
```

## manual calculation usage

see [Manual Calculation](MANUAL_USAGE.md)

### Security

If you've found a bug regarding security please mail [jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of
using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
