## Manual Calculation Usage

this function only available on source manual calculation

### get prayer times from specific latitude, longitude and year

```php
public function getFromLongLatOnSpecificYear(float $latitude, float $longitude, int $year): array
```

example :

```php
Route::get('/year/longlat', function (PrayerTimeInterface $prayer) {
    $prayerTimes = $prayer->getFromLongLatOnSpecificYear(-7.38333, 107.76667, 2024);

    foreach ($prayerTimes as $index => $prayerTime) {
        foreach ($prayerTime as $key => $time) {
            if (in_array($key, ['latitude', 'longitude', 'prayer_at'])) {
                continue;
            }
            $prayerTime[$key] = $time->format('H:i');
        }
        $prayerTimes[$index] = $prayerTime;
    }

    return response()->json([
        'data' => $prayerTimes,
    ]);
});
```

response sample :

```json
{
    "data": [
        {
            "latitude": "-7.38333",
            "longitude": "107.76667",
            "prayer_at": "2024-01-01 00:00:00",
            "imsak": "04:00",
            "subuh": "04:10",
            "terbit": "05:36",
            "dhuha": "06:04",
            "dzuhur": "11:54",
            "ashar": "15:19",
            "maghrib": "18:11",
            "isya": "19:25"
        }
    ]
}
```

### get prayer times from specific latitude, longitude and date

```php
public function getFromLongLatOnSpecificDate(float $latitude, float $longitude, string $date): array
```

example :

```php
Route::get('/date/longlat', function (PrayerTimeInterface $prayer) {
    $prayerTimes = $prayer->getFromLongLatOnSpecificDate(-7.38333, 107.76667, '2024-04-16');

    foreach ($prayerTimes as $key => $prayerTime) {
        if (in_array($key, ['latitude', 'longitude', 'prayer_at'])) {
            continue;
        }
        $prayerTimes[$key] = $prayerTime->format('H:i');
    }

    return response()->json([
        'data' => $prayerTimes,
    ]);
});
```

response sample :

```json
{
    "data": {
        "latitude": "-7.38333",
        "longitude": "107.76667",
        "prayer_at": "2024-04-16 00:00:00",
        "imsak": "04:22",
        "subuh": "04:32",
        "terbit": "05:51",
        "dhuha": "06:17",
        "dzuhur": "11:51",
        "ashar": "15:09",
        "maghrib": "17:49",
        "isya": "18:57"
    }
}
```

### get prayer times from specific external city id and year

```php
public function getFromCityIdOnSpecificYear(string $cityId, int $year): array
```

example :

```php
Route::get('/year/city/{id}', function (PrayerTimeInterface $prayer, string $id) {
    $prayerTimes = $prayer->getFromCityIdOnSpecificYear($id, 2024);

    foreach ($prayerTimes as $index => $prayerTime) {
        foreach ($prayerTime as $key => $time) {
            if (in_array($key, ['latitude', 'longitude', 'prayer_at', 'city_external_id', 'city_name'])) {
                continue;
            }
            $prayerTime[$key] = $time->format('H:i');
        }
        $prayerTimes[$index] = $prayerTime;
    }

    return response()->json([
        'data' => $prayerTimes,
    ]);
});
```

response sample :

```json
{
    "data": [
        {
            "city_external_id": "3204",
            "city_name": "KABUPATEN BANDUNG",
            "latitude": "-7.10000",
            "longitude": "107.60000",
            "prayer_at": "2024-01-01 00:00:00",
            "imsak": "04:01",
            "subuh": "04:11",
            "terbit": "05:37",
            "dhuha": "06:05",
            "dzuhur": "11:55",
            "ashar": "15:20",
            "maghrib": "18:11",
            "isya": "19:25"
        }
    ]
}
```

### get prayer times from specific external city id and date

```php
public function getFromCityIdOnSpecificDate(string $cityId, string $date): array
```

example :

```php
Route::get('/date/city/{id}', function (PrayerTimeInterface $prayer, string $id) {
    $prayerTimes = $prayer->getFromCityIdOnSpecificDate($id, '2024-04-16');

    foreach ($prayerTimes as $key => $prayerTime) {
        if (in_array($key, ['latitude', 'longitude', 'prayer_at', 'city_external_id', 'city_name'])) {
            continue;
        }
        $prayerTimes[$key] = $prayerTime->format('H:i');
    }

    return response()->json([
        'data' => $prayerTimes,
    ]);
});
```

response sample :

```json
{
    "data": {
        "city_external_id": "3204",
        "city_name": "KABUPATEN BANDUNG",
        "latitude": "-7.10000",
        "longitude": "107.60000",
        "prayer_at": "2024-04-16 00:00:00",
        "imsak": "04:23",
        "subuh": "04:33",
        "terbit": "05:51",
        "dhuha": "06:17",
        "dzuhur": "11:51",
        "ashar": "15:09",
        "maghrib": "17:49",
        "isya": "18:58"
    }
}
```
