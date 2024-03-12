<?php

return [
    /**
     * Source of prayertime.
     * options: 'kemenag', 'myquran.com', 'manual calculation'
     */
    'source' => ENV('PRAYERTIME_SOURCE', 'kemenag'),

    /**
     * Base URI of prayertime
     * kemenag: https://bimasislam.kemenag.go.id/
     * myquran.com: https://api.myquran.com/v2/
     * manual calculation:
     */
    'base_uri' => ENV('PRAYTIME_BASE_URI', 'https://bimasislam.kemenag.go.id/'),
];
