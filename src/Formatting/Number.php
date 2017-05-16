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
use InvalidArgumentException;

use Wedeto\I18n\Locale;
use Wedeto\Util\Functions as WF;
use Wedeto\Util\Hook;

/**
 * Format and parse numbers according to a specific locale
 */
class Number
{
    /** The locale to use */
    protected $locale;

    /** The number formatter object */
    protected $number_formatter = null;

    /** The grouping symbol used to group digits in large numbers */
    protected $grouping_symbol;

    /** The decimal symbol separating decimals from the rest of the number */
    protected $decimal_symbol;

    /** The maximum number of decimals to display */
    protected $decimal_precision = 10;

    /**
     * Create the object based on the provided locale
     * @param Locale $locale The locale to use
     */
    public function __construct(Locale $locale)
    {
        $this->locale = $locale;
        $this->number_formatter = new NumberFormatter($this->locale->getLocale(), NumberFormatter::DECIMAL);

        // Allow customization using a hook
        $response = Hook::execute(
            "Wedeto.I18n.Formatting.Number.Create",
            ['object' => $this, 'formatter' => $this->number_formatter]
        );
        $this->number_formatter = $response['formatter'];

        // Extract the thousand separator and decimal point from the number formatter
        $this->decimal_symbol = $this->number_formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $this->grouping_symbol = $this->number_formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    /**
     * Format the provided number.
     * 
     * @param numeric $number The number to format
     * @param int $decimals The maximum amount of decimals to show. Null to use default
     * @return string The formatted string
     */
    public function format($number, $decimals = null)
    {
        if ($decimals !== null && !is_int($decimals))
            throw new InvalidArgumentException("Decimals should be an int, not: " . WF::str($decimals));

        $decimals = $decimals ?? $this->decimal_precision;
        $this->number_formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        return $this->number_formatter->format($number);
    }

    /**
     * Set the default amount of decimals
     * @param int $decimals The maximum number of decimals to display
     * @return Number Provides fluent interface
     */
    public function setDecimalPrecision(int $decimals)
    {
        $this->decimal_precision = $decimals;
        return $this;
    }

    /**
     * Parse a provided numeric string, locale aware
     * @param string $str The string to parse
     * @param numeric The parsed number
     */
    public function parse($str)
    {
        return $this->number_formatter->parse($str);
    }

    /**
     * @return string the symbol used for separating decimals
     */
    public function getDecimalSymbol()
    {
        return $this->decimal_symbol;
    }

    /**
     * @return string the symbol used for grouping thousands
     */
    public function getGroupingSymbol()
    {
        return $this->grouping_symbol;
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
