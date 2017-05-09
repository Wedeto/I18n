<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Copyright 2017, Egbert van der Wal

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this
list of conditions and the following disclaimer. Redistributions in binary form
must reproduce the above copyright notice, this list of conditions and the
following disclaimer in the documentation and/or other materials provided with
the distribution. Neither the name of Zend or Rogue Wave Software, nor the
names of its contributors may be used to endorse or promote products derived
from this software without specific prior written permission. THIS SOFTWARE IS
PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
*/

namespace Wedeto\I18n;

use DateTime;
use DateTimeZone;
use Locale;
use NumberFormatter;

use Wedeto\Util\Functions as WF;

class Formatting
{
    const DATE = 1;
    const TIME = 2;
    const DATETIME = 3;

    private static $init = false;

    private $locale;
    private $number_formatter = null;
    private $currency_formatter = null;
    private $date_format = null;
    private $datetime_format = null;
    private $time_format = null;
    private $timezone = null;
    private $thousand_separator;
    private $decimal_point;

    public function __construct($locale)
    {
        $this->locale = Locale::canonicalize($locale);
        $this->date_format = self::defaultDateFormat();
        $this->time_format = self::defaultTimeFormat();
        $this->datetime_format = self::defaultDateTimeFormat();
        $this->timezone = self::defaultTimezone();
        $this->number_formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        $pattern = trim($this->number_formatter->getPattern(), "#");
        $this->thousand_separator = substr($pattern, 0, 1);
        $this->decimal_point = substr($pattern, -1, 1);
    }

    public static function defaultCurrency()
    {
        $cfg = Config::getConfig();
        return $cfg->dget('localization', 'currency', 'EUR');
    }
    
    public static function defaultTimezone()
    {
        $cfg = Config::getConfig();
        return new DateTimeZone($cfg->dget('localization', 'timezone', 'UTC'));
    }

    public function defaultDateFormat()
    {
        $cfg = Config::getConfig();
        return $cfg->dget('localization', 'dateformat', 'd/m/Y');
    }

    public function defaultDateTimeFormat()
    {
        $cfg = Config::getConfig();
        return $cfg->dget('localization', 'dateformat', 'd/m/Y H:i:s');
    }

    public function defaultTimeFormat()
    {
        $cfg = Config::getConfig();
        return $cfg->dget('localization', 'dateformat', 'H:i:s');
    }

    public function formatNumber($number, $decimals = false)
    {
        if ($decimals !== false)
            return number_format($number, $decimals, $this->thousand_separator, $this->decimal_point);

        return $this->number_formatter->format($number);
    }

    public function parseNumber($str)
    {
        return $this->number_formatter->parse($number);
    }

    public function formatCurrency($number, $currency = null)
    {
        if ($this->currency_formatter === null)
            $this->currency_formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        if ($currency === null)
            $currency = self::defaultCurrency();
        return $this->currency_formatter->formatCurrency($number, $currency);
    }

    public function parseCurrency($str, $currency = null)
    {
        if ($this->currency_formatter === null)
            $this->currency_formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        if ($currency === null)
            $currency = self::defaultCurrency();

        return $this->currency_formatter->parseCurrency($str, $currency);
    }

    public function formatDate($date, $type = I18N::DATE)
    {
        if (!($date instanceof DateTime))
        {
            if (WF::is_int_val($date))
                $date = new DateTime("@" . $date);
            elseif (is_string($date))
                $date = new DateTime($date);
        }

        $date->setTimeZone($this->timezone);

        switch ($type)
        {
            case I18N::DATE:
                return $date->format($this->date_format);
            case I18N::TIME:
                return $date->format($this->time_format);
            case I18N::DATETIME:
            default:
                return $date->format($this->datetime_format);
        }
    }
    
    public function parseDate($datestr, $type = I18N::DATETIME)
    {
        switch ($type)
        {
            case I18N::DATE:
                return DateTime::createFromFormat($this->date_format);
            case I18N::DATETIME:
                return DateTime::createFromFormat($this->datetime_format);
            case I18N::TIME:
                return DateTime::createFromFormat($this->time_format);
            default:
                throw new \DomainException("Invalid date type: $type");
        }
    }

    public function setTimezone($tz)
    {
        if (!($tz instanceof DateTimeZone))
            $tz = new DateTimeZone($tz);

        $this->timezone = $tz;
        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setCurrency($currency)
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setDateFormat($fmt, $type)
    {
        switch ($type)
        {
            case I18N::DATE:
                $this->date_format = $fmt;
                break;
            case I18N::TIME:
                $this->time_format = $fmt;
                break;
            case I18N::DATETIME:
                $this->datetime_format = $fmt;
                break;
            default:
                throw new \DomainException("Invalid date type: $type");
        }
        return $this;
    }
}

WF::check_extension('intl', 'Locale');
