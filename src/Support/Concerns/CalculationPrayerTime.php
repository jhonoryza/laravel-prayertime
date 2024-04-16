<?php

namespace Jhonoryza\LaravelPrayertime\Support\Concerns;

class CalculationPrayerTime extends PrayTime
{
    public $kemenag = 8;    // Indonesia - Kemenag

    public function PrayTime($methodID = 0)
    {
        /*  var $methodParams[methodNum] = array(fa, ms, mv, is, iv);

               fa : fajr angle
               ms : maghrib selector (0 = angle; 1 = minutes after sunset)
               mv : maghrib parameter value (in angle or minutes)
               is : isha selector (0 = angle; 1 = minutes after maghrib)
               iv : isha parameter value (in angle or minutes)
       */
        $this->methodParams[$this->kemenag] = [20, 1, 0, 0, 18];

        parent::PrayTime($methodID);
    }
}
