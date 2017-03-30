<?php
/*
This is part of WASP, the Web Application Software Platform.
It is published under the MIT Open Source License.

Copyright 2017, Egbert van der Wal

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace WASP\I18n

use DateTime;
use DateTimeZone;
use Locale;
use NumberFormatter;

use WASP\Util\Function as WF;

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

check_extension('intl', 'Locale');
