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
{
    use WASP\I18n\Translator\Translator;
    use WASP\Dir;
    use WASP\Debug;
    use WASP\Path;
    use Locale;
    use Dictionary;

    class Translate
    {
        private static $translator = null;
        private static $locale = null;
        private static $stack = array();
        private static $locales = array();
        private static $domains = array();

        public static function getLocaleList($textDomain = null)
        {
            if ($textDomain === null)
                return array_keys(self::$locales);
        
            if (!isset(self::$domains[$textDomain]))
                return array();

            $result = array();
        }

        public static function findBestLocale($locale, $textDomain = null)
        {
            $locale_data = new Dictionary(Locale::parseLocale($locale));
            $language = $locale_data->get('language');
            if (empty($language))
                return null;

            $locales = $this->getLocaleList($textDomain);
            $region = $language . '_' . $locale_data->get('region', '');
            if (isset($locales[$region]))
                return $region;

            return isset($locales[$language]) ? $language : null;
        }

        /**
         * Reset the global state of the object
         */
        public static function reset()
        {
            if (!defined('WASP_TEST') || constant('WASP_TEST') === 0) return;
            self::$translator = null;
            $this->locale = null;
            $this->locales = array();
            $this->domains = array();
            $this->stack = array();
        }
        
        public static function setLocale($locale)
        {
            $locale = self::findBestLocale($locale);
            self::$translator->setLocale($locale);
            return $locale;
        }

        public static function translate(string $msgid, string $domain = "", array $values = array())
        {
            $domain = empty($domain) ? null : $domain;
            $str = $this->translator->translate($msgid, $domain, self::$locale);
            $str = gettext($msg);
            if (count($values))
            {
                array_unshift($values, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }
        
        public static function translatePlural($string msgid, string $plural, int $n, string $domain = "", array $values = array())
        {
            $str = $this->translator->translatePlural($msgid, $plural, $n, $domain);

            if (count($values))
            {
                array_unshift($values, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }

        public static function pushDomain($domain)
        {
            array_push(self::$stack, self::$translator->getTextDomain());
            self::$translator->setTextDomain($domain);
        }

        public static function popDomain()
        {
            $prev = array_pop(self::$stack);
            if ($prev)
                self::$translator->setTextDomain($prev);
            return $prev;
        }

        public static function setupTranslation($module, $path, $classname)
        {
            if (self::$translator === null)
            {
                self::$translator = new Translator();
                self::$translator->setFallbackLocale('en');
            }

            if ($classname !== null)
            {
                $domains = $classname::getTextDomains();
                if (!is_array($domains) && !($domains instanceof \Iterator))
                    $domains = array();
            }
            elseif ($module === "core")
                $domains = array("core");
            else
                return;

            if (count($domains) === 0)
                return;

            $lang_path = $path;
            if (!file_exists($lang_path) || !is_dir($lang_path))
            {
                Debug\error("WASP.Translate", "Language directory {0} does not exist for module {1}", [$lang_path, $module]);
                return;
            }

            // Bind all text domains for this module
            foreach ($domains as $domain)
            {
                if (is_string($domain))
                {
                    self::$translator->addPattern($lang_path, '%s/' . $domain . '.mo', $domain);
                    Debug\debug("WASP.Translate", "Bound text domain {0} to path {1}", [$domain, $lang_path]);
                }

                if (!isset($domains[$domain]))
                    self::$domains[$domain] = array();

                foreach (new Dir($lang_path, Dir::READ_DIR) as $entry)
                {
                    if (!isset(self::$locales[$entry]))
                        self::$locales[$entry] = array($domain);
                    else
                        self::$locales[$entry][] = $domain;
                    self::$domains[$domain][$entry] = true;
                }
            }
            
            if (self::$translator->getLocale() === null)
            {
                $keys = array_keys(self::$locales);
                self::setLocale(reset($keys));
            }
        }

    }

    $path = Path::current();
    Translate::setupTranslation('core', $path->core . '/language', null);
}

namespace
{
    function t(string $msgid, array $values = array())
    {
        return Translate::translate($msg, $values);
    }

    function tn(string $msgid, string $plural, int $n, array $values = array());
    {
        return Translate::translatePlural($msgid, $plural, $n);
    }

    function td(string $msgid, string $domain, array $values = array())
    {
        return Translate::translate($msgid, $domain, $values);
    }

    function tdn(string $msgid, string $plural, int $n, string $domain, array $values = array())
    {
        return Translate::translatePlural($msgid, $plural, $n, $domain, $values);
    }

    function setTextDomain($dom)
    {
        return Translate::pushDomain($dom);
    }

    function resetTextDomain()
    {
        return Translate::popDomain();
    }

    function tl()
    {
        $texts = func_get_args();
        $langs = WASP\Template::$last_template->translations;

        if (count($texts) < count($langs))
            $langs = array_slice($langs, 0, count($texts));
        elseif (count($texts) > count($langs))
            $texts = array_slice($texts, 0, count($langs));

        $translations = array_combine($langs, $texts);
        $language = WASP\Request::$language;

        $text = isset($translations[$language]) ? $translations[$language] : reset($translations);

        return $text;
    }
}
