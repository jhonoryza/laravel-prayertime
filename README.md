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

config example prayertime.php

```php
<?php

return [
    /**
     * Source of prayertime.
     * options: 'kemenag', 'myquran.com', 'manual calculation'
     */
    'source' => ENV('PRAYERTIME_SOURCE', 'kemenag'),

    /**
     * Base URI of prayertime
     * example: https://bimasislam.kemenag.go.id/
     * example: https://api.myquran.com/v2/
     */
    'base_uri' => ENV('PRAYTIME_BASE_URI', 'https://bimasislam.kemenag.go.id/'),
];
```

kemenag source is using scraping method to get the data

myquran.com source is using rest api method to get the data

manual calculation source is using manual calculation method

## sync data

sync city and province data

```bash
php artisan pray:sync-city
```

sync prayer times

```bash
php artisan pray:sync-times
```

### Security

If you've found a bug regarding security please mail [jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of
using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
