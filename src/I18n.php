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

use Wedeto\Util\LoggerAwareStaticTrait;
use Wedeto\I18n\Translator\Translator;
use Wedeto\IO\DirReader;

/**
 * Provide localization functions - an interface to the GetText-file based
 * translation system and access to appropriate formatters for numbers and
 * currency.
 *
 * It also offers an easy to implement translation system to use in-line
 * translations.
 */
class I18n
{
    /** The default translation instance */
    protected static $default = null;

    /** The translator object, interfacing with the language .po and .mo files. */
    protected $translator;

    /** The money formatter */
    protected $money_formatter;

    /** The number formatter */
    protected $number_formatter;

    /** The locale in use */
    protected $locale;

    /** The language for the configured locale, used for translateList */
    protected $language;

    /** The locales available for translation */
    protected $locales = array();

    /** The domains available in translations */
    protected $domains = array();

    /** 
     * @return Translate The current translation instance
     */
    public static function getDefault()
    {
        if (empty(self::$default))
            throw new I18nException("No default I18n instance has been set");
        return self::$default;
    }

    /**
     * Set the default translate instance
     *
     * @param Translate $trl The instance to set as default
     */
    public static function setDefault(Translate $trl)
    {
        self::$instance = $trl;
    }

    /** 
     * Create the I18n object with a specified locale
     */
    public function __construct(Locale $locale = null)
    {
        $this->translator = new Translator();
        $this->translator->setFallbackLocale('en');
        $this->setLocale($locale ?: new Locale('c'));
    }

    /**
     * Get the list of locales that have localizations.
     * @param string $textDomain The text domain to narrow the search
     * @return array The list of locales for the text domain, or in general
     */
    public function getLocaleList($textDomain = null)
    {
        if ($textDomain === null)
            return array_keys($this->locales);
    
        if (!isset($this->domains[$textDomain]))
            return array();

        return array_keys($this->domains[$textDomain]);
    }

    /**
     * Change the locale. The locale will be matched to available localizations and the 
     * best matching will be set. 
     *
     * @param Locale $locale The locale to set
     * @return string The locale that was set
     */
    public function setLocale(Locale $locale)
    {
        $this->locale = $locale;
        $this->translator->setLocale($locale);
        $this->money_formatter = null;
        $this->number_formatter = null;
        $this->language = $locale->getLanguage();
        return $locale;
    }

    /**
     * @return Locale The active Locale object
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return Formatting\Number a number formatter for the current locale
     */
    public function getNumberFormatter()
    {
        if ($this->number_formatter === null)
            $this->number_formatter = new Formatting\Number($this->locale);
        return $this->number_formatter;
    }

    /**
     * @return Formatting\Money A money formatter for the current locale
     */
    public function getMoneyFormatter()
    {
        if ($this->money_formatter === null)
            $this->money_formatter = new Formatting\Money($this->locale);
        return $this->money_formatter;
    }

    /**
     * @return Translate\Translator The translator for the current locale
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Format a message, locale aware. You can substitute values in the message,
     * where a type specifier can be appended after each variable name after a colon.
     *
     * Valid type specifiers are:
     *          :i for integer, 
     *          :f for float, 
     *          :Nf for a float with N decimals
     *          :c for a currency value
     *          :s for a string
     *          :b for a bool
     * @param string $message The message to format
     * @param array $values The values available to put in the message
     * @return string The formatted string
     */
    public function formatMessage(string $message, array $values)
    {
        if (
            count($values) === 0 || 
            !preg_match_all("/{([\w\d_]+)(:(([0-9]*)f|c|i|s|b))?}/", $message, $matches, PREG_SET_ORDER)
        )
        {
            // Nothing to replace
            return $message;
        }

        $tokens = [];
        foreach ($matches as $match)
        {
            if (isset($tokens[$match[0]]))
                continue; // Already handled

            $name = $match[1];
            if (!isset($values[$name]))
                continue;

            $token = $match[0];
            $val = $values[$name];

            if (count($match) === 2)
            {
                // No type specifier, base on actual data type
                if (is_numeric($val))
                {
                    $fmt = $this->getNumberFormatter();
                    $tokens[$token] = $fmt->format($val);
                }
                else
                {
                    $tokens[$token] = (string)$val;
                }
            }
            elseif (count($match) === 5 || $match[3] === "f")
            {
                // Float
                $precision = (int)($match[4] ?? null);

                if (is_numeric($val))
                {
                    $fmt = $this->getNumberFormatter();
                    $tokens[$token] = $fmt->format($val, $precision);
                }
            }
            elseif ($match[3] === "s")
            {
                // String
                $tokens[$token] = (string)$val;
            }
            else if ($match[3] === "i")
            {
                // Int value
                if (is_numeric($val))
                {
                    $fmt = $this->getNumberFormatter();
                    $tokens[$token] = $fmt->format($val, 0);
                }
            }
            elseif ($match[3] === "c")
            {
                // Currency
                if (is_numeric($val))
                {
                    $fmt = $this->getMoneyFormatter();
                    $tokens[$token] = $fmt->format($val);
                }
            }
            elseif ($match[3] === "b")
            {
                // Boolean value
                $val = (bool)$val;
                $tokens[$token] = $this->translate($val ? 'true' : 'false');
            }
        }

        // Replace all tokens
        if (count($tokens))
            $message = str_replace(array_keys($tokens), array_values($tokens), $message);

        return $message;
    }

    /** 
     * Low overhead translation facility. The caller provides an array with
     * keys being the languages. Based on the configured language, the
     * appropriate translation is returned. If that is not available, the first
     * one in the list is returned.
     *
     * @param array $translation The translated strings, in language => translation pairs.
     * @return string The best matching translation
     */
    public function translateList(array $translations, array $values = null)
    {
        $msg = (string)($translations[$this->language] ?? reset($translations));
        return empty($values) ? $msg : $this->formatMessage($msg, $values);
    }

    /**
     * Use the Translator to translate the provided string.
     *
     * @param string $msgid The message to translate
     * @param string $domain The text domain to use. Omit to use default
     * @param array $values The values to replace for the placeholders, using sprintf
     * @return The translated string
     * @see Translate::setTextDomain
     */
    public function translate(string $msgid, string $domain = null, array $values = array())
    {
        return $this->formatMessage($this->translator->translate($msgid, $domain, $this->locale), $values);
    }
    
    /**
     * Use the Translator to translate the provided singular/plural string.
     * 
     * @param string $msgid The message to translate, singular form
     * @param string $plural The message to translate, plural form
     * @param string $n The number used to select the proper translation
     * @param string $domain The text domain to use. Omit to use default
     * @param array $values The values to replace for the placeholders, using sprintf
     * @return The translated string
     * @see Translate::setTextDomain
     */
    public function translatePlural(string $msgid, string $plural, int $n, string $domain = null, array $values = array())
    {
        return $this->formatMessage($this->translator->translatePlural($msgid, $plural, $n, $domain), $values);
    }

    /**
     * Change the text domain to a new domain, while remembering the previous
     *
     * @param string $domain The domain to use
     * @return string The previously set text domain
     */
    public function setTextDomain(string $domain)
    {
        $old = $this->getTextDomain();
        $this->translator->setTextDomain($domain);
        return $old;
    }

    /**
     * @return string The current text domain
     */
    public function getTextDomain()
    {
        return $this->translator->getTextDomain();
    }

    /**
     * Add gettext translation files for a module
     *
     * @param string $module The module / text domain
     * @param string $path The path where to load the translations from
     * @return I18n Provides fluent interface
     */
    public function registerTextDomain(string $domain, string $path)
    {
        if (!file_exists($path) || !is_dir($path))
        {
            self::$logger->error("Language directory {0} does not exist for text domain {1}", [$path, $domain]);
            return;
        }

        $this->translator->addPattern($path, '%s/' . $domain . '.mo', $domain);
        if (!isset($domains[$domain]))
            $this->domains[$domain] = array();

        foreach (new DirReader($path, DirReader::READ_DIR) as $entry)
        {
            if (!isset($this->locales[$entry]))
                $this->locales[$entry] = array($domain);
            else
                $this->locales[$entry][] = $domain;
            $this->domains[$domain][$entry] = true;
        }
        return $this;
    }
}
