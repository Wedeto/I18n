<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Copyright 2017, Egbert van der Wal <wedeto at pointpro dot nl>

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
use InvalidArgumentException;
use Wedeto\I18n\Locale;
use Wedeto\I18n\I18nException;

use Wedeto\Util\Functions as WF;
use Wedeto\Util\Hook;

/**
 * Format monetary numbers
 */
class Money
{
    /** The locale in use */
    protected $locale;

    /** The currency NumberFormatter object */
    protected $currency_formatter = null;

    /** The currency used for formatting */
    protected $currency;

    /** Currency optional -> use number parses when normal parser fails */
    protected $fallback_parser;

    /**
     * Create a formatter for the specified locale. The locale should contain
     * the monetary information - the currency in use.
     * You can explicity specify this by using keywords, for example, you could
     * use en_US@currency=JPY to use US formatting with JPY as currency.
     *
     * @param Locale|string $locale The locale used for formatting
     */
    public function __construct($locale)
    {
        $this->locale = Locale::create($locale);
        $this->currency_formatter = new NumberFormatter($this->locale->getLocale(), NumberFormatter::CURRENCY);
        $this->fallback_parser = new Number($locale);

        // Allow customization using a hook
        $response = Hook::execute(
            "Wedeto.I18n.Formatting.Money.Create",
            ['object' => $this, 'formatter' => $this->currency_formatter]
        );
        $this->currency_formatter = $response['formatter'];

        // Extract currency symbols
        $this->currency_code = $this->currency_formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        $this->currency_symbol = $this->currency_formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Format a number as money
     *
     * @param float $number The amount to format
     * @param string $currency The currency to use. When omitted, the default is used
     * @return string The formatted money string
     */
    public function format(float $number, string $currency = null)
    {
        $currency = $currency ?: $this->getCurrencyCode();
        $str = $this->currency_formatter->formatCurrency($number, $currency);
        if ($str === false)
            throw new I18nException("Invalid currency: " . $currency);
        return $str;
    }

    /**
     * Parse a money string into an number
     * @param string $str The string to parse
     * @param string $currency The currency unit
     * @return double The parsed currency
     */
    public function parse(string $str, $currency = null)
    {
        $currency = $currency ?: $this->getCurrencyCode();
        $amount = $this->currency_formatter->parseCurrency($str, $currency);

        // Fallback to a different parser if the main one fails - eg a number parser.
        if ($amount === false && $this->fallback_parser !== null)
            $amount = $this->fallback_parser->parse($str);

        if ($amount === false)
            throw new I18nException("Cannot parse value: " . $str);

        return $amount;
    }

    /**
     * @return string The currency symbol for the locale
     */
    public function getCurrencySymbol()
    {
        return $this->currency_symbol;
    }

    /**
     * @return string The currency code for the locale
     */
    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    /**
     * Set a fallback parser that is used when the currency
     * parser doesn't work. The default is a number formatter
     * with the same locale, to make the currency symbol optional.
     *
     * @param object $fallback_parsed The argument must be an object and have a
     *                                method:
     *  
     *                                Parser#parse($number_str) that returns a float.
     *                                  
     *                                Pass in null to disable the fallback parser.
     * @return Money Provides fluent interface
     * @throws InvalidArgumentException When an invalid parser is provided
     */
    public function setFallbackParser($fallback_parser)
    {
        if (empty($fallback_parser))
            $this->fallback_parser = null;
        elseif (is_object($fallback_parser) && method_exists($fallback_parser, 'parse'))
            $this->fallback_parser = $fallback_parser;
        else
            throw new InvalidArgumentException("Parser must have a parse method");
        return $this;
    }

    /**
     * @return Locale The locale object associated with the formatter
     */
    public function getLocale()
    {
        return $this->locale;
    }
}

// @codeCoverageIgnoreStart
WF::check_extension('intl', 'NumberFormatter');
// @codeCoverageIgnoreEnd
