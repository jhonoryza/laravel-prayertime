<?php

return [
    /**
     * Source of prayertime.
     *
     * Supported: 'kemenag', 'myquran.com', 'manual calculation', 'custom'
     * default: 'manual calculation'
     */
    'source' => ENV('PRAYERTIME_SOURCE', 'manual calculation'),

    /**
     * manual calculation source
     *
     * Supported: "praytimes.org, geniusts/prayer-times, islamic-network/prayer-times"
     * default: 'geniusts/prayer-times'
     */
    'manual_source' => ENV('PRAYTIME_MANUAL_SOURCE', 'geniusts/prayer-times'),

    /**
     * Base URI of prayertime
     *
     * kemenag: https://bimasislam.kemenag.go.id/
     * myquran.com: https://api.myquran.com/v2/
     * manual calculation: `leave this empty`
     * custom: `free to customize`
     */
    'base_uri' => ENV('PRAYTIME_BASE_URI'),

    /**
     * define your custom prayer time class if source is using custom
     *
     * you can check this class as reference
     *
     * @see \Jhonoryza\LaravelPrayertime\Support\KemenagPrayerTime
     * @see \Jhonoryza\LaravelPrayertime\Support\MyQuranPrayerTime
     * @see \Jhonoryza\LaravelPrayertime\Support\ManualPrayerTime
     */
    'custom_prayer_time_class' => null,
];
