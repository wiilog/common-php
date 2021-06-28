<?php

namespace WiiCommon;

use \DateTime as PhpDateTime;
use DateTimeZone;

class DateTime extends PhpDateTime {
    private static string $DEFAULT_TIMEZONE = 'Europe/Paris';

    public function __construct($datetime = 'now', DateTimeZone $timezone = null) {
        parent::__construct($datetime, $timezone ?? new DateTimeZone(self::$DEFAULT_TIMEZONE));
    }
}
