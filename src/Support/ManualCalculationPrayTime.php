<?php

namespace Jhonoryza\LaravelPrayertime\Support;

//--------------------- Copyright Block ----------------------
/*

ManualCalculationPrayTime.php: Prayer Times Calculator (ver 1.2.2)
Copyright (C) 2007-2010 PrayTimes.org

Developer: Hamid Zarrabi-Zadeh
License: GNU LGPL v3.0

TERMS OF USE:
    Permission is granted to use this code, with or
    without modification, in any website or application
    provided that credit is given to the original work
    with a link back to PrayTimes.org.

This program is distributed in the hope that it will
be useful, but WITHOUT ANY WARRANTY.

PLEASE DO NOT REMOVE THIS COPYRIGHT BLOCK.

*/

//--------------------- Help and Manual ----------------------
/*

User's Manual:
http://praytimes.org/manual

Calculating Formulas:
http://praytimes.org/calculation



//------------------------ User Interface -------------------------


    getPrayerTimes (timestamp, latitude, longitude, timeZone)
    getDatePrayerTimes (year, month, day, latitude, longitude, timeZone)

    setCalcMethod (methodID)
    setAsrMethod (methodID)

    setFajrAngle (angle)
    setMaghribAngle (angle)
    setIshaAngle (angle)
    setDhuhrMinutes (minutes)    // minutes after mid-day
    setMaghribMinutes (minutes)  // minutes after sunset
    setIshaMinutes (minutes)     // minutes after maghrib

    setHighLatsMethod (methodID) // adjust method for higher latitudes

    setTimeFormat (timeFormat)
    floatToTime24 (time)
    floatToTime12 (time)
    floatToTime12NS (time)


//------------------------- Sample Usage --------------------------


    $prayTime->setCalcMethod($prayTime->ISNA);
    $times = $prayTime->getPrayerTimes(time(), 43, -80, -5);
    print('Sunrise = '. $times[1]);


*/

//--------------------- ManualCalculationPrayTime Class -----------------------

use Carbon\Carbon;

class ManualCalculationPrayTime
{
    //------------------------ Constants --------------------------

    // Calculation Methods
    public $Jafari = 0;    // Ithna Ashari

    public $Karachi = 1;    // University of Islamic Sciences, Karachi

    public $ISNA = 2;    // Islamic Society of North America (ISNA)

    public $MWL = 3;    // Muslim World League (MWL)

    public $Makkah = 4;    // Umm al-Qura, Makkah

    public $Egypt = 5;    // Egyptian General Authority of Survey

    public $Custom = 6;    // Custom Setting

    public $Tehran = 7;    // Institute of Geophysics, University of Tehran

    public $Kemenag = 8;    // Kemenag

    // Juristic Methods
    public $Shafii = 0;    // Shafii (standard)

    public $Hanafi = 1;    // Hanafi

    // Adjusting Methods for Higher Latitudes
    public $None = 0;    // No adjustment

    public $MidNight = 1;    // middle of night

    public $OneSeventh = 2;    // 1/7th of night

    public $AngleBased = 3;    // angle/60th of night

    // Time Formats
    public $Time24 = 10;    // 24-hour format

    public $Time12 = 1;    // 12-hour format

    public $Time12NS = 2;    // 12-hour format with no suffix

    public $Float = 6;    // floating point number

    // Time Names
    public $timeNames = [
        'Fajr',
        'Sunrise',
        'Dhuhr',
        'Asr',
        'Sunset',
        'Maghrib',
        'Isha',
    ];

    public $InvalidTime = '-----';     // The string used for invalid times

    //---------------------- Global Variables --------------------

    public $calcMethod = 0;        // caculation method

    public $asrJuristic = 0;        // Juristic method for Asr

    public $dhuhrMinutes = 1;        // minutes after mid-day for Dhuhr

    public $adjustHighLats = 3;    // adjusting method for higher latitudes

    public $timeFormat = 0;        // time format

    public $lat;        // latitude

    public $lng;        // longitude

    public $timeZone;   // time-zone

    public $JDate;      // Julian date

    //--------------------- Technical Settings --------------------

    public $numIterations = 10;        // number of iterations needed to compute times

    //------------------- Calc Method Parameters --------------------

    public $methodParams = [];

    /*  var $methodParams[methodNum] = array(fa, ms, mv, is, iv);

            fa : fajr angle
            ms : maghrib selector (0 = angle; 1 = minutes after sunset)
            mv : maghrib parameter value (in angle or minutes)
            is : isha selector (0 = angle; 1 = minutes after maghrib)
            iv : isha parameter value (in angle or minutes)
    */

    //----------------------- Constructors -------------------------

    public function PrayTime($methodID = 0)
    {

        $this->methodParams[$this->Jafari]  = [16, 0, 4, 0, 14];
        $this->methodParams[$this->Karachi] = [18, 1, 0, 0, 18];
        $this->methodParams[$this->ISNA]    = [15, 1, 0, 0, 15];
        $this->methodParams[$this->MWL]     = [18, 1, 0, 0, 17];
        $this->methodParams[$this->Makkah]  = [18.5, 1, 0, 1, 90];
        $this->methodParams[$this->Egypt]   = [19.5, 1, 0, 0, 17.5];
        $this->methodParams[$this->Tehran]  = [17.7, 0, 4.5, 0, 14];
        $this->methodParams[$this->Custom]  = [18, 1, 0, 0, 17];
        $this->methodParams[$this->Kemenag] = [20, 1, 0, 0, 18];

        $this->setCalcMethod($methodID);
    }

    public function __construct($methodID = 0)
    {
        $this->PrayTime($methodID);
    }

    //-------------------- Interface Functions --------------------

    // return prayer times for a given date
    public function getDatePrayerTimes($year, $month, $day, $latitude, $longitude, $timeZone)
    {
        $this->lat      = $latitude;
        $this->lng      = $longitude;
        $this->timeZone = $timeZone;
        $this->JDate    = $this->julianDate($year, $month, $day) - $longitude / (15 * 24);

        return $this->computeDayTimes();
    }

    // return prayer times for a given timestamp
    public function getPrayerTimes($timestamp, $latitude, $longitude, $timeZone)
    {
        $date = @getdate($timestamp);

        return $this->getDatePrayerTimes($date['year'], $date['mon'], $date['mday'],
            $latitude, $longitude, $timeZone);
    }

    // set the calculation method
    public function setCalcMethod($methodID)
    {
        $this->calcMethod = $methodID;
    }

    // set the juristic method for Asr
    public function setAsrMethod($methodID)
    {
        if ($methodID < 0 || $methodID > 1) {
            return;
        }
        $this->asrJuristic = $methodID;
    }

    // set the angle for calculating Fajr
    public function setFajrAngle($angle)
    {
        $this->setCustomParams([$angle, null, null, null, null]);
    }

    // set the angle for calculating Maghrib
    public function setMaghribAngle($angle)
    {
        $this->setCustomParams([null, 0, $angle, null, null]);
    }

    // set the angle for calculating Isha
    public function setIshaAngle($angle)
    {
        $this->setCustomParams([null, null, null, 0, $angle]);
    }

    // set the minutes after mid-day for calculating Dhuhr
    public function setDhuhrMinutes($minutes)
    {
        $this->dhuhrMinutes = $minutes;
    }

    // set the minutes after Sunset for calculating Maghrib
    public function setMaghribMinutes($minutes)
    {
        $this->setCustomParams([null, 1, $minutes, null, null]);
    }

    // set the minutes after Maghrib for calculating Isha
    public function setIshaMinutes($minutes)
    {
        $this->setCustomParams([null, null, null, 1, $minutes]);
    }

    // set custom values for calculation parameters
    public function setCustomParams($params)
    {
        for ($i = 0; $i < 5; $i++) {
            if ($params[$i] == null) {
                $this->methodParams[$this->Custom][$i] = $this->methodParams[$this->calcMethod][$i];
            } else {
                $this->methodParams[$this->Custom][$i] = $params[$i];
            }
        }
        $this->calcMethod = $this->Custom;
    }

    // set adjusting method for higher latitudes
    public function setHighLatsMethod($methodID)
    {
        $this->adjustHighLats = $methodID;
    }

    // set the time format
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;
    }

    // convert float hours to 24h format
    public function floatToTime24($time)
    {
        if (is_nan($time)) {
            return $this->InvalidTime;
        }
        $time    = $this->fixhour($time + 0.5 / 60);  // add 0.5 minutes to round
        $hours   = round($time);
        $minutes = round(($time - $hours) * 60);
        //        $second = round($minutes / 60);
        $carbon = Carbon::today()
            ->addHours($hours)
            ->addMinutes($minutes);
        //            ->addSeconds($second);

        return $carbon->format('H:i');
        //        return $this->twoDigitsFormat($hours).':'.$this->twoDigitsFormat($minutes).':'.$this->twoDigitsFormat($second);
    }

    // convert float hours to 12h format
    public function floatToTime12($time, $noSuffix = false)
    {
        if (is_nan($time)) {
            return $this->InvalidTime;
        }
        $time    = $this->fixhour($time + 0.5 / 60);  // add 0.5 minutes to round
        $hours   = floor($time);
        $minutes = floor(($time - $hours) * 60);
        $suffix  = $hours >= 12 ? ' pm' : ' am';
        $hours   = ($hours + 12 - 1) % 12 + 1;

        return $hours . ':' . $this->twoDigitsFormat($minutes) . ($noSuffix ? '' : $suffix);
    }

    // convert float hours to 12h format with no suffix
    public function floatToTime12NS($time)
    {
        return $this->floatToTime12($time, true);
    }

    //---------------------- Calculation Functions -----------------------

    // References:
    // http://www.ummah.net/astronomy/saltime
    // http://aa.usno.navy.mil/faq/docs/SunApprox.html

    // compute declination angle of sun and equation of time
    public function sunPosition($jd)
    {
        $D = $jd - 2451545.0;
        $g = $this->fixangle(357.529 + 0.98560028 * $D);
        $q = $this->fixangle(280.459 + 0.98564736 * $D);
        $L = $this->fixangle($q + 1.915 * $this->dsin($g) + 0.020 * $this->dsin(2 * $g));

        $R = 1.00014 - 0.01671    * $this->dcos($g) - 0.00014 * $this->dcos(2 * $g);
        $e = 23.439  - 0.00000036 * $D;

        $d   = $this->darcsin($this->dsin($e) * $this->dsin($L));
        $RA  = $this->darctan2($this->dcos($e) * $this->dsin($L), $this->dcos($L)) / 15;
        $RA  = $this->fixhour($RA);
        $EqT = $q / 15 - $RA;

        return [$d, $EqT];
    }

    // compute equation of time
    public function equationOfTime($jd)
    {
        $sp = $this->sunPosition($jd);

        return $sp[1];
    }

    // compute declination angle of sun
    public function sunDeclination($jd)
    {
        $sp = $this->sunPosition($jd);

        return $sp[0];
    }

    // compute mid-day (Dhuhr, Zawal) time
    public function computeMidDay($t)
    {
        $T = $this->equationOfTime($this->JDate + $t);
        $Z = $this->fixhour(12 - $T);

        return $Z;
    }

    // compute time for a given angle G
    public function computeTime($G, $t)
    {
        $D = $this->sunDeclination($this->JDate + $t);
        $Z = $this->computeMidDay($t);
        $V = 1 / 15 * $this->darccos((-$this->dsin($G) - $this->dsin($D) * $this->dsin($this->lat)) / ($this->dcos($D) * $this->dcos($this->lat)));

        return $Z + ($G > 90 ? -$V : $V);
    }

    // compute the time of Asr
    public function computeAsr($step, $t)  // Shafii: step=1, Hanafi: step=2
    {
        $D = $this->sunDeclination($this->JDate + $t);
        $G = -$this->darccot($step + $this->dtan(abs($this->lat - $D)));

        return $this->computeTime($G, $t);
    }

    //---------------------- Compute Prayer Times -----------------------

    // compute prayer times at given julian date
    public function computeTimes($times)
    {
        $t = $this->dayPortion($times);

        $Fajr    = $this->computeTime(180 - $this->methodParams[$this->calcMethod][0], $t[0]);
        $Sunrise = $this->computeTime(180 - 0.833, $t[1]);
        $Dhuhr   = $this->computeMidDay($t[2]);
        $Asr     = $this->computeAsr(1 + $this->asrJuristic, $t[3]);
        $Sunset  = $this->computeTime(0.833, $t[4]);
        $Maghrib = $this->computeTime($this->methodParams[$this->calcMethod][2], $t[5]);
        $Isha    = $this->computeTime($this->methodParams[$this->calcMethod][4], $t[6]);

        return [$Fajr, $Sunrise, $Dhuhr, $Asr, $Sunset, $Maghrib, $Isha];
    }

    // compute prayer times at given julian date
    public function computeDayTimes()
    {
        $times = [5, 6, 12, 13, 18, 18, 18]; //default times

        for ($i = 1; $i <= $this->numIterations; $i++) {
            $times = $this->computeTimes($times);
        }

        $times = $this->adjustTimes($times);

        return $this->adjustTimesFormat($times);
    }

    // adjust times in a prayer time array
    public function adjustTimes($times)
    {
        for ($i = 0; $i < 7; $i++) {
            $times[$i] += $this->timeZone - $this->lng / 15;
        }
        $times[2] += $this->dhuhrMinutes / 60; //Dhuhr
        if ($this->methodParams[$this->calcMethod][1] == 1) { // Maghrib
            $times[5] = $times[4] + $this->methodParams[$this->calcMethod][2] / 60;
        }
        if ($this->methodParams[$this->calcMethod][3] == 1) { // Isha
            $times[6] = $times[5] + $this->methodParams[$this->calcMethod][4] / 60;
        }

        if ($this->adjustHighLats != $this->None) {
            $times = $this->adjustHighLatTimes($times);
        }

        return $times;
    }

    // convert times array to given time format
    public function adjustTimesFormat($times)
    {
        if ($this->timeFormat == $this->Float) {
            return $times;
        }
        for ($i = 0; $i < 7; $i++) {
            if ($this->timeFormat == $this->Time12) {
                $times[$i] = $this->floatToTime12($times[$i]);
            } elseif ($this->timeFormat == $this->Time12NS) {
                $times[$i] = $this->floatToTime12($times[$i], true);
            } else {
                $times[$i] = $this->floatToTime24($times[$i]);
            }
        }

        return $times;
    }

    // adjust Fajr, Isha and Maghrib for locations in higher latitudes
    public function adjustHighLatTimes($times)
    {
        $nightTime = $this->timeDiff($times[4], $times[1]); // sunset to sunrise

        // Adjust Fajr
        $FajrDiff = $this->nightPortion($this->methodParams[$this->calcMethod][0]) * $nightTime;
        if (is_nan($times[0]) || $this->timeDiff($times[0], $times[1]) > $FajrDiff) {
            $times[0] = $times[1] - $FajrDiff;
        }

        // Adjust Isha
        $IshaAngle = ($this->methodParams[$this->calcMethod][3] == 0) ? $this->methodParams[$this->calcMethod][4] : 18;
        $IshaDiff  = $this->nightPortion($IshaAngle) * $nightTime;
        if (is_nan($times[6]) || $this->timeDiff($times[4], $times[6]) > $IshaDiff) {
            $times[6] = $times[4] + $IshaDiff;
        }

        // Adjust Maghrib
        $MaghribAngle = ($this->methodParams[$this->calcMethod][1] == 0) ? $this->methodParams[$this->calcMethod][2] : 4;
        $MaghribDiff  = $this->nightPortion($MaghribAngle) * $nightTime;
        if (is_nan($times[5]) || $this->timeDiff($times[4], $times[5]) > $MaghribDiff) {
            $times[5] = $times[4] + $MaghribDiff;
        }

        return $times;
    }

    // the night portion used for adjusting times in higher latitudes
    public function nightPortion($angle)
    {
        if ($this->adjustHighLats == $this->AngleBased) {
            return 1 / 60 * $angle;
        }
        if ($this->adjustHighLats == $this->MidNight) {
            return 1 / 2;
        }
        if ($this->adjustHighLats == $this->OneSeventh) {
            return 1 / 7;
        }
    }

    // convert hours to day portions
    public function dayPortion($times)
    {
        for ($i = 0; $i < 7; $i++) {
            $times[$i] /= 24;
        }

        return $times;
    }

    //---------------------- Misc Functions -----------------------

    // compute the difference between two times
    public function timeDiff($time1, $time2)
    {
        return $this->fixhour($time2 - $time1);
    }

    // add a leading 0 if necessary
    public function twoDigitsFormat($num)
    {
        return ($num < 10) ? '0' . $num : $num;
    }

    //---------------------- Julian Date Functions -----------------------

    // calculate julian date from a calendar date
    public function julianDate($year, $month, $day)
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);

        $JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;

        return $JD;
    }

    // convert a calendar date to julian date (second method)
    public function calcJD($year, $month, $day)
    {
        $J1970 = 2440588.0;
        $date  = $year . '-' . $month . '-' . $day;
        $ms    = strtotime($date);   // # of milliseconds since midnight Jan 1, 1970
        $days  = floor($ms / (1000 * 60 * 60 * 24));

        return $J1970 + $days - 0.5;
    }

    //---------------------- Trigonometric Functions -----------------------

    // degree sin
    public function dsin($d)
    {
        return sin($this->dtr($d));
    }

    // degree cos
    public function dcos($d)
    {
        return cos($this->dtr($d));
    }

    // degree tan
    public function dtan($d)
    {
        return tan($this->dtr($d));
    }

    // degree arcsin
    public function darcsin($x)
    {
        return $this->rtd(asin($x));
    }

    // degree arccos
    public function darccos($x)
    {
        return $this->rtd(acos($x));
    }

    // degree arctan
    public function darctan($x)
    {
        return $this->rtd(atan($x));
    }

    // degree arctan2
    public function darctan2($y, $x)
    {
        return $this->rtd(atan2($y, $x));
    }

    // degree arccot
    public function darccot($x)
    {
        return $this->rtd(atan(1 / $x));
    }

    // degree to radian
    public function dtr($d)
    {
        return ($d * M_PI) / 180.0;
    }

    // radian to degree
    public function rtd($r)
    {
        return ($r * 180.0) / M_PI;
    }

    // range reduce angle in degrees.
    public function fixangle($a)
    {
        $a = $a - 360.0 * floor($a / 360.0);
        $a = $a < 0 ? $a + 360.0 : $a;

        return $a;
    }

    // range reduce hours to 0..23
    public function fixhour($a)
    {
        $a = $a - 24.0 * floor($a / 24.0);
        $a = $a < 0 ? $a + 24.0 : $a;

        return $a;
    }
}
