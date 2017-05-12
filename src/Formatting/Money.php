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

namespace Wedeto\I18n\Formatting;

use NumberFormatter;
use Wedeto\I18n\Locale;

/**
 * Format monetary numbers
 */
class Money
{
    /** The locale in use */
    private $locale;

    /** The currency NumberFormatter object */
    private $currency_formatter = null;

    /** The currency used for formatting */
    private $currency;

    /**
     * Create a formatter
     * @param Locale $locale The locale used for formatting
     * @param string $currency The currency
     */
    public function __construct(Locale $locale, string $currency = "€")
    {
        $this->locale = $locale;
        $this->currency_formatter = new NumberFormatter($this->locale->getLocale(), NumberFormatter::CURRENCY);
        $this->currency = $currency;
    }

    /**
     * Format a number as money
     *
     * @param float $number The amount to format
     * @param string $currency The currency to use. When omitted, the default is used
     * @return string The formatted money string
     */
    public function format(float $number, $currency = null)
    {
        $currency = $currency ?: $this->getCurrency();
        return $this->currency_formatter->formatCurrency($number, $currency);
    }

    /**
     * Parse a money string into an number
     * @param string $str The string to parse
     * @param string $currency The currency unit
     * @return double The parsed currency
     */
    public function parse(string $str, $currency = null)
    {
        if ($this->currency_formatter === null)
            $this->currency_formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        $currency = $currency ?: $this->getCurrency();
        $amount = $this->currency_formatter->parseCurrency($str, $currency);
        if ($amount === false)
            throw new \InvalidArgumentException("Cannot parse value: " . $str);
        return $amount;
    }

    /**
     * Set the default currency for this object
     * @param string $currency The currency to set
     * @return Money Provides fluent interface
     */
    public function setCurrency(string $currency)
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    /**
     * @return string The currency currency in use
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}

WF::check_extension('intl', 'NumberFormatter');
