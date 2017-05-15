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

namespace Wedeto\I18n
{
    /**
     * Loader class to load this file, enabling I18n shortcuts
     */
    class I18nShortcut
    {
        public static function load()
        {}
    }
}

namespace
{
    use Wedeto\I18n\I18n;

    /**
     * @see Wedeto\I18n\I18n::translate
     */
    function t(string $msgid, array $values = array())
    {
        return I18n::getDefault()->translate($msgid, "", $values);
    }

    /**
     * @see Wedeto\I18n\I18n::translatePlural
     */
    function tn(string $msgid, string $plural, int $n, array $values = array())
    {
        return I18n::getDefault()->translatePlural($msgid, $plural, $n, "", $values);
    }

    /**
     * @see Wedeto\I18n\I18n::translate
     */
    function td(string $msgid, string $domain, array $values = array())
    {
        return I18n::getDefault()->translate($msgid, $domain, $values);
    }

    /**
     * @see Wedeto\I18n\I18n::translatePlural
     */
    function tdn(string $msgid, string $plural, int $n, string $domain, array $values = array())
    {
        return I18n::getDefault()->translatePlural($msgid, $plural, $n, $domain, $values);
    }

    /**
     * @see Wedeto\I18n\I18n::setTextDomain
     */
    function setTextDomain($dom)
    {
        return I18n::getDefault()->setTextDomain($dom);
    }

    /**
     * @see Wedeto\I18n\I18n::translateList
     */
    function tl(array $translations, array $values = [])
    {
        return I18n::getDefault()->translateList($translations);
    }

    /**
     * @see Wedeto\I18n\Formatting\Number::format
     */
    function localize_number(float $number)
    {
        return I18n::getDefault()->getNumberFormatter()->format($number);
    }

    /**
     * @see Wedeto\I18n\Formatting\Money::format
     */
    function localize_money(float $number)
    {
        return I18n::getDefault()->getMoneyFormatter()->format($number);
    }

    function localize_message(string $msg, array $values)
    {
        return I18n::getDefault()->formatMessage($msg, $values);
    }
}
