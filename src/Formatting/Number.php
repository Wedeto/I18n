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

use NumberFormatter;

use Wedeto\I18n\Locale;
use Wedeto\Util\Functions as WF;

/**
 * Format and parse numbers according to a specific locale
 */
class Number
{
    /** The locale to use */
    private $locale;

    /** The number formatter object */
    private $number_formatter = null;

    /** The thousand separator */
    private $thousand_separator;

    /** The decimal point */
    private $decimal_point;

    /**
     * Create the object based on the provided locale
     * @param Locale $locale The locale to use
     */
    public function __construct(Locale $locale)
    {
        $this->locale = $locale;
        $this->number_formatter = new NumberFormatter($this->locale->getLocale(), NumberFormatter::DECIMAL);

        // Extract the thousand separator and decimal point from the number formatter
        $pattern = trim($this->number_formatter->getPattern(), "#");
        $this->thousand_separator = substr($pattern, 0, 1);
        $this->decimal_point = substr($pattern, -1, 1);
    }

    /**
     * Format the provided number.
     * 
     * @param numeric $number The number to format
     * @param int $decimals The amount of decimals to show. When omitted, all decimals are used
     * @return string The formatted string
     */
    public function format($number, int $decimals = null)
    {
        if ($decimals !== null)
        {
            // NumberFormatter doesn't provide a way to limit the amount of decimals on the fly, only by changing
            // the pattern explicitly. number_format is an easier way to accomplish this.
            return number_format($number, $decimals, $this->thousand_separator, $this->decimal_point);
        }

        return $this->number_formatter->format($number);
    }

    /**
     * Parse a provided numeric string, locale aware
     * @param string $str The string to parse
     * @param numeric The parsed number
     */
    public function parse($str)
    {
        return $this->number_formatter->parse($number);
    }
}

WF::check_extension('intl', 'NumberFormatter');
