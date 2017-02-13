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

    class Translate
    {
        private static $translator = null;
        private static $stack = array();
        private static $locales = array();
        private static $domains = array();

        public static function getLocaleList($textDomain = null)
        {

        }

        public static function translate($msg)
        {
            if (is_array($msg))
                $args = $msg;
            else
                $args = func_get_args();

            if (count($args) < 1)
                throw new I18nException("Not enough parameters specified");
            $msg = array_shift($args);

            $str = $this->translator->translate($msg);
            $str = gettext($msg);
            if (count($args))
            {
                array_unshift($args, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }

        public static function translateDomain($domain)
        {
            if (is_array($domain))
                $args = $domain;
            else
                $args = func_get_args();

            if (count($args) < 2)
                throw new I18nException("Not enough parameters specified");
            $domain = array_shift($args);
            $msg = array_shift($args);

            $str = $this->translator->translate($msg, $domain);
            if (count($args))
            {
                array_unshift($args, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }
        
        public static function translatePlural($msg)
        {
            if (is_array($msg))
                $args = $msg;
            else
                $args = func_get_args();

            if (count($args) < 3)
                throw new I18nException("Not enough parameters specified");

            $msg_singular = array_shift($args);
            $msg_plural = array_shift($args);
            $msg_number = $args[0];

            $str = $this->translator->translatePlural($msg_singular, $msg_plural, $msg_number);

            if (count($args))
            {
                array_unshift($args, $str);
                $str = call_user_func_array('sprintf', $args);
            }

            return $str;
        }

        public static function translatePluralDomain($domain)
        {
            if (is_array($domain))
                $args = $domain;
            else
                $args = func_get_args();

            if (count($args) < 4)
                throw new I18nException("Not enough parameters specified");

            $msg_domain = array_shift($args);
            $msg_singular = array_shift($args);
            $msg_plural = array_shift($args);
            $msg_number = $args[0];

            $str = $this->translator->translatePlural($msg_singular, $msg_plural, $msg_number, $msg_domain);

            if (count($args))
            {
                array_unshift($args, $str);
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
        }

        public static function setupTranslation($module, $path, $classname)
        {
            if (self::$translator === null)
                self::$translator = new Translator();

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
        }

    }

    Translate::setupTranslation('core', WASP_ROOT . '/core/language', null);
}

namespace
{
    function t()
    {
        return Translate::translate(func_get_args()); 
    }

    function tn()
    {
        return Translate::translatePlural(func_get_args()); 
    }

    function td()
    {
        return Translate::translateDomain(func_get_args());
    }

    function tdn()
    {
        return Translate::translatePluralDomain(func_get_args());
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
