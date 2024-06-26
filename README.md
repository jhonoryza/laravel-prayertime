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

## manual calculation usage

see [Manual Calculation](MANUAL_USAGE.md)

### Security

If you've found a bug regarding security please mail [jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of
using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
