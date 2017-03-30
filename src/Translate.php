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
    use Locale;

    use Wedeto\Util\LoggerAwareStaticTrait;
    use Wedeto\I18n\Translator\Translator;
    use Wedeto\IO\DirReader;
    use Wedeto\Util\Dictionary;

    /**
     * Translate provides the translation system for Wedeto.
     * It has a easy to implement translation system to use in-line translations,
     * and uses a Translator based on Zend\\I18n\Translator to read GetText files
     * for more sophisticated translations.
     */
    class Translate
    {
        use LoggerAwareStaticTrait;

        protected static $instance = null;

        private $translator;
        private $locale;
        private $stack = array();
        private $locales = array();
        private $domains = array();
        private $language = 'en';
        private $translations = array();

        /** 
         * @return Translate The current translation instance
         */
        public static function getInstance()
        {
            if (empty(self::$instance))
                self::$instance = new Translate;

            return self::$instance;
        }

        /**
         * Set the default translate instance
         *
         * @param Translate $trl The instance to set as default
         */
        public static function setInstance(Translate $trl)
        {
            self::$instance = $trl;
        }

        public function __construct()
        {
            self::getLogger();
            $this->translator = new Translator();
            $this->translator->setFallbackLocale('en');
            $this->locale = 'en';
            $this->language = 'en';
            self::$instance = $this;
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

            $result = array();
        }

        /**
         * Find the best matching localization that exists matching the
         * specified locale. It will first try to use a region-specific
         * localization, fall back to the general language localization
         * and if that also does not exist, the fallback locale will be 
         * returned.
         * @param string $locale The locale to find
         * @param string $textDomain The text domain used to narrow the search.
         *                           May be null to search all locales
         * @return string The best matching localization available
         */
        public function findBestLocale(string $locale, $textDomain = null)
        {
            $locale_data = new Dictionary(Locale::parseLocale($locale));
            $language = $locale_data->get('language');
            if (empty($language))
                return 'C';

            $locales = $this->getLocaleList($textDomain);
            $region = $language . '_' . $locale_data->get('region', '');
            if (isset($locales[$region]))
                return $region;

            return isset($locales[$language]) ? $language : $this->translator->GetFallbackLocale();
        }

        /**
         * Change the locale. The locale will be matched to available localizations and the 
         * best matching will be set. 
         *
         * @param string $locale The locale to set
         * @return string The locale that was set
         */
        public function setLocale(string $locale)
        {
            if ($locale !== 'C')
                $locale = $this->findBestLocale($locale);

            $this->translator->setLocale($locale);
            $this->locale = $locale;
            $this->language = $locale;
            return $locale;
        }

        /**
         * For the basic translator, set the order in which languages
         * are provided to Translate::translateList. This allows
         * localization in-line, by calling translateList like so:
         * $translate->translateList('english', 'dutch'), after specifying
         * that order with $translate->setLanguageOrder('en', 'nl');
         *
         * @param string $locale The locales in correct order
         * @return Translate Provides fluent interface
         */
        public function setLanguageOrder()
        {
            $args = func_get_args();
            $this->translations = is_array($args[0]) ? $args[0] : $args;
            return $this;
        }

        /**
         * Set the language to be used by Translate::translateList. The
         * locale will also be updated to match the provided language as good
         * as possible.
         *
         * @param string $language The language to be used
         * @return Translate Provides fluent interface
         */
        public function setLanguage($language)
        {
            $this->setLocale($language);
            $this->language = $language;
            return $this;
        }

        /** 
         * Low overhead translation facility. The provided arguments will be interpreted
         * as translations based on the languages set using Translate::setLanguageOrder.
         * The correct one will be returned. If the configured language is not available,
         * the first is returned.
         *
         * @param string $translation The translated strings
         * @return string The best matching translation
         */
        public function translateList()
        {
            $texts = func_get_args();
            if (is_array($texts[0]))
                $texts = $texts[0];

            if ($this->language === 'C')
                return reset($texts);

            // Get the list of languages and match them with the provided translations
            $langs = $this->translations;
            if (count($texts) < count($langs))
                $langs = array_slice($langs, 0, count($texts));
            elseif (count($texts) > count($langs))
                $texts = array_slice($texts, 0, count($langs));

            // Merge the languages and translations
            $translations = array_combine($langs, $texts);
            
            // Return the appropriate translation
            $language = $this->language;
            $text = isset($translations[$language]) ? $translations[$language] : reset($translations);
            return $text;
        }

        /**
         * Use the Translator to translate the provided string.
         *
         * @param string $msgid The message to translate
         * @param string $domain The text domain to use. Omit to use default
         * @param array $values The values to replace for the placeholders, using sprintf
         * @return The translated string
         * @see Translate::pushDomain
         */
        public function translate(string $msgid, string $domain = "", array $values = array())
        {
            $domain = empty($domain) ? null : $domain;
            $str = $this->translator->translate($msgid, $domain, $this->locale);
            $str = gettext($msgid);
            if (count($values))
            {
                array_unshift($values, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
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
         * @see Translate::pushdomain
         */
        public function translatePlural(string $msgid, string $plural, int $n, string $domain = "", array $values = array())
        {
            $str = $this->translator->translatePlural($msgid, $plural, $n, $domain);

            if (count($values))
            {
                array_unshift($values, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }

        /**
         * Change the text domain to a new domain, while remembering the previous
         *
         * @param string $domain The domain to use
         * @return Translate Provides fluent interface
         */
        public function pushDomain(string $domain)
        {
            array_push($this->stack, $this->translator->getTextDomain());
            $this->translator->setTextDomain($domain);
            return $this;
        }

        /**
         * Restore the previous text domain
         *
         * @return Translate Provides fluent interface
         */
        public function popDomain()
        {
            $prev = array_pop($this->stack);
            if ($prev)
                $this->translator->setTextDomain($prev);
            return $prev;
        }

        /**
         * Pops text domains until the specified level is reached
         *
         * @param int $level The number of domains on the stack to get to
         * @return Translate Provides fluent interface
         */
        public function popDomainsTo(int $level)
        {
            $level = max(0, $level);
            while (count($this->stack) > $level)
                $this->popDomain();
            return $this;
        }

        /**
         * @return string The current text domain
         */
        public function getTextDomain()
        {
            return $this->translator->getTextDomain();
        }

        /**
         * @return int The current number of text domains on the stack
         */
        public function getDomainStackSize()
        {
            return count($this->stack);
        }

        /**
         * Add gettext translation files for a module
         *
         * @param string $module The module 
         * @param string $path The path where to load the translations from
         * @param string $classname Dunno
         */
        public function registerTextDomain($domain, $path)
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
        }
    }
}

namespace
{
    /**
     * @see Wedeto\I18n\Translate\translate
     */
    function t(string $msgid, array $values = array())
    {
        return Translate::getInstance()->translate($msgid, "", $values);
    }

    /**
     * @see Wedeto\I18n\Translate\translatePlural
     */
    function tn(string $msgid, string $plural, int $n, array $values = array())
    {
        return Translate::getInstance()->translatePlural($msgid, $plural, $n, "", $values);
    }

    /**
     * @see Wedeto\I18n\Translate\translate
     */
    function td(string $msgid, string $domain, array $values = array())
    {
        return Translate::getInstance()->translate($msgid, $domain, $values);
    }

    /**
     * @see Wedeto\I18n\Translate\translatePlural
     */
    function tdn(string $msgid, string $plural, int $n, string $domain, array $values = array())
    {
        return Translate::getInstance()->translatePlural($msgid, $plural, $n, $domain, $values);
    }

    /**
     * @see Wedeto\I18n\Translate\pushDomain
     */
    function setTextDomain($dom)
    {
        return Translate::getInstance()->pushDomain($dom);
    }

    /**
     * @see Wedeto\I18n\Translate\popDomain
     */
    function resetTextDomain()
    {
        return Translate::getInstance()->popDomain();
    }

    /**
     * @see Wedeto\I18n\Translate\translateList
     */
    function tl()
    {
        return Translate::getInstance()->translateList(func_get_args());
    }
        
    /**
     * @see Wedeto\I18n\Translate\setLanguageOrder
     */
    function tlSetLanguageOrder()
    {
        $tr = Translate::getInstance();
        call_user_func_array(array($tr, 'setLanguageOrder'), func_get_args());
    }
}
