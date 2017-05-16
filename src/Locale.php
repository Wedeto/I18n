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

namespace Wedeto\I18n;

use Wedeto\Util\Hook;

/**
 * Locale represents a Locale identifier. It will be validated to have a correct syntax,
 * and will provide names and information about the locale.
 *
 * For more information about the underlying Locale system, see:
 * http://userguide.icu-project.org/locale
 */
class Locale
{
    protected $locale;

    /** Internal cache for fallback locale list */
    private $fallback_list = null;

    /** Internal cache for display names */
    private $data = [];

    /**
     * Construct the locale object. The locale will be specified
     * by a valid Locale string. The locale will be canonicalized, using
     * level 2 locatization as described in: 
     * 
     * http://userguide.icu-project.org/locale#TOC-Canonicalization
     *
     * @throws I18nException If the canonicalization fails or an empty locale
     * is specified.
     */
    public function __construct(string $locale)
    {
        if (empty($locale))
            throw new I18nException("Empty locale: $locale");

        if (preg_match("/[^a-zA-Z0-9_@=-]/", $locale) || strlen($locale) > 32)
            throw new I18nException("Invalid locale: $locale");

        $this->locale = \Locale::canonicalize($locale);

        if (empty($this->locale))
        {
            // @codeCoverageIgnoreStart
            // Canonicalize will basically accept everything that the preg above accepts.
            // Just a fail-safe check to make sure parsing succeeded.
            throw new I18nException("Invalid locale: $locale");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Create a locale from a string. If a Locale object is passed in,
     * the same object is returned unaltered.
     *
     * @param string|Locale $locale
     */
    public static function create($locale)
    {
        return ($locale instanceof Locale) ? $locale : new Locale($locale);
    }


    /**
     * Return a Locale object for the default locale, as set by
     * \Locale::setDefault, or in PHP's INI Value default_locale.
     */
    public static function getDefault()
    {
        return new Locale(\Locale::getDefault());
    }

    /**
     * @return string The full locale set
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return array A list of fallback locales, mainly useful for translations.
     *               The topmost will be the most specific one, dropping one level
     *               of specifity for each new item, until only the language remains.
     */
    public function getFallbackList()
    {
        if ($this->fallback_list === null)
        {
            $language = $this->getLanguage(); 
            $region = $this->getRegion();
            $script = $this->getScript();

            $data = ["language" => $this->getLanguage()];
            if (!empty($script))
                $data['script'] = $script;
            if (!empty($region))
                $data['region'] = $region;

            $variants = $this->getVariants();
            foreach ($variants as $idx => $var)
                $data['variant' . ($idx + 1)] = $var;

            $this->fallback_list = [];
            while (count($data) > 0)
            {
                $locale = \Locale::composeLocale($data);
                $this->fallback_list[] = new Locale($locale);
                array_pop($data); 
            }
        }

        return $this->fallback_list;
    }

    /**
     * @return string The language of the locale. 
     */
    public function getLanguage()
    {
        return \Locale::getPrimaryLanguage($this->locale);
    }

    /**
     * @return string The region of the locale. May be empty if not set in the locale.
     */
    public function getRegion()
    {
        return \Locale::getRegion($this->locale);
    }

    /**
     * @return string The variants of the locale. May be an empty array if none are set.
     */
    public function getVariants()
    {
        $vars = \Locale::getAllVariants($this->locale);
        return empty($vars) ? [] : $vars;
    }

    /**
     * @return string The script of the locale. May be empty if not set in the locale.
     */
    public function getScript()
    {
        return \Locale::getScript($this->locale);
    }

    /**
     * @return array A list of keywords in the locale specifier, such as collations, currencies etc.
     */
    public function getKeywords()
    {
        $kws = \Locale::getKeywords($this->locale);
        if (empty($kws))
            return [];
        return $kws;
    }

    /**
     * Obtain a user friendly name for the locale specification
     *
     * @param Locale $locale The locale to display in. May be null to use the default
     * @return string The locale display name.
     */
    public function getDisplayName(Locale $in_locale = null)
    {
        return $this->getDisplayItem('display_name', 'GetDisplayName', 'getDisplayName', $in_locale);
    }

    /**
     * Obtain a user friendly name for the locale language
     *
     * @param Locale $locale The langauge to display in. May be null to use the default
     * @return string The language display name.
     */
    public function getDisplayLanguage(Locale $in_locale = null)
    {
        return $this->getDisplayItem('display_language', 'GetDisplayLanguage', 'getDisplayLanguage', $in_locale);
    }

    /**
     * Obtain a user friendly name for the region.
     *
     * @param Locale $locale The locale to display in. May be null to use the default
     * @return string The display region name. Can be an empty string when no region is set in the locale
     */
    public function getDisplayRegion(Locale $in_locale = null)
    {
        return $this->getDisplayItem('display_region', 'GetDisplayRegion', 'getDisplayRegion', $in_locale);
    }

    /**
     * Obtain a user friendly name for the script.
     *
     * @param Locale $locale The locale to display in. May be null to use the default
     * @return string The display script name. Can be an empty string when no script is set in the locale
     */
    public function getDisplayScript(Locale $in_locale = null)
    {
        return $this->getDisplayItem('display_script', 'GetDisplayScript', 'getDisplayScript', $in_locale);
    }

    /**
     * Obtain a user friendly name for the variant.
     *
     * @param Locale $locale The locale to display in. May be null to use the default
     * @return string The display variant name. Can be an empty string when no variant is set in the locale
     */
    public function getDisplayVariant(Locale $in_locale = null)
    {
        return $this->getDisplayItem('display_variant', 'GetDisplayVariant', 'getDisplayVariant', $in_locale);
    }

    /**
     * Internal helper method that caches a datum and requests it when it is not available.
     * When requesting, the default is the Locale function, but this may be altered by
     * adding a hook. This can be used to provide display names for
     * non-standard languages, for example.
     *
     * @param string $name The datum to get
     * @param string $hook The name of the Hook to execute
     * @param string $method The method in the Locale class to call to obtain the answer
     * @return mixed The resulting information
     */
    protected function getDisplayItem(string $name, string $hook, string $method, Locale $in_locale = null)
    {
        $in_locale = $in_locale ?: Locale::getDefault();
        $in_locale = $in_locale->getLocale();
        if (!isset($this->data[$in_locale][$name]))
        {
            $datum = !empty($method) ? \Locale::$method($this->locale, $in_locale) : '';
            $response = Hook::execute("Wedeto.I18n.Locale." . $hook, ['locale' => $this->locale, $name => $datum, 'in_locale' => $in_locale]);
            $this->data[$in_locale][$name] = $response[$name];
        }
        return $this->data[$in_locale][$name];
    }

    public function __toString()
    {
        return $this->locale;
    }
}
