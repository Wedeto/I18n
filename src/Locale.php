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

use Wedeto\Util\Hook;

class Locale
{
    protected $locale;
    protected $data = [];

    public function __construct(string $locale)
    {
        if (empty($locale))
            throw new I18nException("Empty locale: $locale");

        $this->locale = \Locale::canonicalize($locale);
        if (empty($this->locale))
            throw new I18nException("Invalid locale: $locale");
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getLanguage()
    {
        return $this->getDatum('language', 'GetLanguage', 'getPrimaryLanguage');
    }

    public function getRegion()
    {
        return $this->getDatum('region', 'GetRegion', 'getRegion');
    }

    public function getVariant()
    {
        return $this->getDatum('variant', 'GetVariant', 'getVariant');
    }

    public function getScript()
    {
        return $this->getDatum('script', 'GetScript', 'getScript');
    }

    public function getDisplayLanguage()
    {
        return $this->getDatum('display_language', 'GetDisplayLanguage', 'getDisplayLanguage');
    }

    public function getDisplayName()
    {
        return $this->getDatum('display_name', 'GetDisplayName', 'getDisplayName');
    }

    public function getDisplayRegion()
    {
        return $this->getDatum('display_region', 'GetDisplayRegion', 'getDisplayRegion');
    }

    public function getDisplayScript()
    {
        return $this->getDatum('display_script', 'GetDisplayScript', 'getDisplayScript');
    }

    public function getDisplayVariant()
    {
        return $this->getDatum('display_variant', 'GetDisplayVariant', 'getDisplayVariant');
    }

    protected function getDatum(string $name, string $hook, string $method)
    {
        if (!isset($this->data[$name]))
        {
            $datum = !empty($method) ? Locale::$method($this->locale) : '';
            $response = Hook::execute("Wedeto.I18n.Locale." . $hook, ['locale' => $this->locale, $name => $datum]);
            $this->data[$name] = $resp[$name];
        }
        return $this->data[$name];
    }
}
