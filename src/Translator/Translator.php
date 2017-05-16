<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\Translator was adapted from Zend\I18n\Translator\Translator.
The modifications are: Copyright 2017, Egbert van der Wal <wedeto at pointpro dot nl>

The original source code is copyright Zend Technologies USA Inc. The original
licence information is included below.

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

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wedeto\I18n\Translator;

use InvalidArgumentException;

use Wedeto\I18n\Locale;
use Wedeto\Util\LoggerAwareStaticTrait;
use Wedeto\Util\Cache;
use Wedeto\Util\Dictionary;
use Wedeto\Util\Functions as WF;

/**
 * Translator.
 */
class Translator
{
    use LoggerAwareStaticTrait;

    /** Messages loaded by the translator. */
    protected $messages = array();

    /** Patterns used for loading messages. */
    protected $patterns = array();

    /** Default locale. */
    protected $locale;

    /** Locale to use as fallback if there is no translation. */
    protected $fallback_locale;

    /** The list of locales, in order of preference */
    protected $locale_list = null;
    
    /** Default text domain */
    protected $textDomain = 'default';

    /** Translation cache. */
    protected $cache;

    /** Instantiate a translator */
    public function __construct($locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();
        self::getLogger();
        $this->cache = new Dictionary;
        $this->setLocale($locale);
    }

    /**
     * Add a cache that will be used to load and save translations from
     * all textdomains and locales.
     * @param Cache $cache The cache to use
     * @return Translator Provides fluent interface
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Set the default locale.
     *
     * @param  string $locale
     * @return Translator Provides fluent interface
     */
    public function setLocale($locale)
    {
        $locale = Locale::create($locale);
        $this->locale = $locale;
        $this->rebuildLocaleList();

        return $this;
    }

    /** 
     * Create the list of locale and locale fallback, based on the
     * default locale and the fallback locale. Every translated message
     * is looked up in each of these in order, until a match is found.
     *
     * The list is reversed to make sure the most preferred item is the last,
     * as array_pop is more efficient than array_shift.
     */
    protected function rebuildLocaleList()
    {
        if ($this->fallback_locale !== null)
        {
            $this->locale_list = array_reverse(array_merge(
                $this->locale->getFallbackList(),
                $this->fallback_locale->getFallbackList()
            ));
        }
        else
        {
            $this->locale_list = array_reverse($this->locale->getFallbackList());
        }
    }

    /**
     * @return Locale The locale used for translations
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the fallback locale.
     *
     * @param Locale $locale The locale to use as fallback. Null to disable
     * @return Translator Provides fluent interface
     */
    public function setFallbackLocale($locale)
    {
        if ($locale !== null && !($locale instanceof Locale))
            throw new InvalidArgumentException("Invalid locale: " . WF::str($locale));

        $this->fallback_locale = $locale;
        $this->rebuildLocaleList();
        return $this;
    }

    /**
     * @return Locale the fallback locale.
     */
    public function getFallbackLocale()
    {
        return $this->fallback_locale;
    }

    /**
     * Set the default text domain
     *
     * @param string $domain The domain to set
     * @return Translator Provides fluent interface
     */
    public function setTextDomain(string $domain)
    {
        $this->textDomain = $domain;
        return $this;
    }

    /**
     * Get the current default text domain
     * @return string The current default text domain
     */
    public function getTextDomain()
    {
        return $this->textDomain;
    }

    /*
     * Translate a message.
     *
     * @param string $message The message to translate
     * @param string $textDomain The text domain of the message. Null to use default.
     * @param string $locale The locale for the translation
     * @return string The translated string
     */
    public function translate(string $message, string $textDomain = null, string $locale = null)
    {
        $locale_list = (array)($locale ?: $this->locale_list);
        $locale = end($locale_list);
        $translation = null;
        if ($locale !== 'c')
        {
            $textDomain = $textDomain ?: $this->getTextDomain();
            $translation = $this->getTranslatedMessage($message, null, $locale_list, $textDomain, $locale);
        }

        if (!empty($translation))
            return $translation;

        return $message;
    }

    /**
     * Translate a plural message.
     *
     * @param string $singular The singular version
     * @param string $plural The plural version
     * @param int $number The number determining which version should be used
     * @param string $textDomain The text domain of the message. Null to use default.
     * @param string|null $locale The locale for the translation
     * @return string The translated string
     * @throws \OutOfBoundsException If the returned index does not exist in the translated string.
     */
    public function translatePlural(string $singular, string $plural, int $number, string $textDomain = null, string $locale = null)
    {
        $locale_list = (array)($locale ?: $this->locale_list);
        $locale = end($locale_list);
        $translation = null;
        if ($locale !== 'c')
        {
            $textDomain = $textDomain ?: $this->getTextDomain();
            $translation = $this->getTranslatedMessage($singular, $plural, $locale_list, $textDomain, $locale);
        }

        if (empty($translation))
            return $number == 1 ? $singular : $plural;

        if (is_string($translation))
            $translation = array($translation);

        $index = $this->cache->get($textDomain, $locale)
            ->getPluralRule()
            ->evaluate($number);

        if (!isset($translation[$index]))
            throw new \OutOfBoundsException(sprintf('Provided index %d does not exist in plural array', $index));

        return $translation[$index];
    }

    /**
     * Get a translated message.
     *
     * @param string $message The message to be translated
     * @param string $locale_list The list of locales to try
     * @param string $textDomain The text domain for the message
     * @param string &$locale Set to the locale providing the message
     * @return string|array|null The translation, set of translations or null if no translation is available
     */
    protected function getTranslatedMessage(string $message, $plural = null, array $locale_list, string $textDomain, string &$locale)
    {
        if (empty($message))
            return '';

        while (!empty($locale_list))
        {
            $locale = array_pop($locale_list)->getLocale();
            if (!$this->cache->has($textDomain, $locale))
                $this->loadMessages($textDomain, $locale);

            $td = $this->cache->get($textDomain, $locale);

            if ($td->has($message))
                return $td->get($message);

            // Log untranslated message
            $context = ['msgid' => $message, 'locale' => $locale, 'domain '=> $textDomain];
            if ($plural !== null)
                $context['msgid_plural'] = $plural;

            self::$logger->debug(
                "Untranslated message: \"{msgid}\"", 
                $context
            );
        }
        return null;
    }

    /**
     * Add multiple translations with a file pattern.
     *
     * @param string $baseDir
     * @param string $pattern
     * @param string $textDomain
     * @return Translator Provides fluent interface
     */
    public function addPattern(string $baseDir, string $pattern, string $textDomain = 'default')
    {
        if (!isset($this->patterns[$textDomain]))
            $this->patterns[$textDomain] = array();

        $this->patterns[$textDomain][] = array(
            'baseDir' => rtrim($baseDir, '/'),
            'pattern' => $pattern,
        );

        return $this;
    }

    /**
     * Load messages for a given language and domain.
     *
     * @param string $textDomain
     * @param string $locale
     * @throws RuntimeException
     * @return void
     */
    protected function loadMessages(string $textDomain, string $locale)
    {
        $messagesLoaded = $this->loadMessagesFromPatterns($textDomain, $locale);

        if (!$messagesLoaded)
            $this->injectMessages($textDomain, new TextDomain, $locale);
    }

    /**
     * Load messages from patterns.
     *
     * @param string $textDomain
     * @param string $locale
     * @return bool
     */
    protected function loadMessagesFromPatterns(string $textDomain, string $locale)
    {
        $messagesLoaded = false;

        if (isset($this->patterns[$textDomain]))
        {
            foreach ($this->patterns[$textDomain] as $pattern)
            {
                $filename = $pattern['baseDir'] . '/' . sprintf($pattern['pattern'], $locale);

                if (!is_file($filename))
                    continue;
                $loader = new GetText;
                $messages = $loader->load($locale, $filename);
                $this->injectMessages($textDomain, $messages, $locale);
                $messagesLoaded = true;
            }
        }

        return $messagesLoaded;
    }

    /** 
     * Inject custom loaded messages
     * @param string $textDomain The text domain
     * @param TextDomain $messages The messages to inject
     * @param string $locale The locale for the messages
     * @return Translator Provides fluent interface
     */
    public function injectMessages(string $textDomain, TextDomain $messages, string $locale)
    {
        if ($this->cache->has($textDomain, $locale))
            $this->cache->get($textDomain, $locale)->merge($messages);
        else
            $this->cache->set($textDomain, $locale, $messages);
        return $this;
    }

    /**
     * Return all the messages.
     *
     * @param string $textDomain
     * @param null $locale
     *
     * @return mixed
     */
    public function getAllMessages(string $textDomain = 'default', Locale $locale = null)
    {
        $locale = ($locale ?: $this->getLocale())->getLocale();

        if (!$this->cache->has($textDomain, $locale))
            $this->loadMessages($textDomain, $locale);

        return $this->cache->get($textDomain, $locale);
    }
}
