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
