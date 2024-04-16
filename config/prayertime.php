<?php

return [
    /**
     * Source of prayertime.
     * options: 'kemenag', 'myquran.com', 'manual calculation', 'custom'
     */
    'source' => ENV('PRAYERTIME_SOURCE', 'manual calculation'),

    /**
     * Base URI of prayertime
     * kemenag: https://bimasislam.kemenag.go.id/
     * myquran.com: https://api.myquran.com/v2/
     * manual calculation:
     * custom:
     */
    'base_uri' => ENV('PRAYTIME_BASE_URI'),

    /**
     * when using source 'manual calculation' you can choose to use
     * package geniusts/prayer-times to do the calculation or using this class
     *
     * @see \Jhonoryza\LaravelPrayertime\Support\Concerns\CalculationPrayerTime
     */
    'use_package_geniusts_prayer_times' => true,

    /**
     * define your custom prayer time class if source is using custom
     * you can check this class as reference
     *
     * @see \Jhonoryza\LaravelPrayertime\Support\KemenagPrayerTime
     * @see \Jhonoryza\LaravelPrayertime\Support\MyQuranPrayerTime
     * @see \Jhonoryza\LaravelPrayertime\Support\ManualPrayerTime
     */
    'custom_prayer_time_class' => null,
];
