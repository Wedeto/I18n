<?php
/**
 * This is part of WASP, the Web Application Software Platform.
 * This class is adapted from Zend/I18n/Translator/TextDomain.
 * The Zend framework is published on the New BSD license, and as such,
 * this class is also covered by the New BSD license as a derivative work.
 * The original copright notice is maintained below.
 */

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace WASP\I18n\Translator;

use WASP\Dictionary;
use WASP\I18n\Translator\Plural\Rule as PluralRule;

/**
 * Text domain.
 */
class TextDomain extends Dictionary
{
    /** Plural rule */
    protected $pluralRule;

    /**
     * Default plural rule shared between instances.
     */
    protected static $defaultPluralRule;

    /**
     * Set the plural rule
     *
     * @param  PluralRule $rule
     * @return TextDomain
     */
    public function setPluralRule(PluralRule $rule)
    {
        $this->pluralRule = $rule;
        return $this;
    }

    /**
     * Get the plural rule.
     *
     * @param  bool $fallbackToDefaultRule
     * @return PluralRule|null
     */
    public function getPluralRule($fallbackToDefaultRule = true)
    {
        if ($this->pluralRule === null && $fallbackToDefaultRule)
            return static::getDefaultPluralRule();

        return $this->pluralRule;
    }

    /**
     * Checks whether the text domain has a plural rule.
     *
     * @return bool
     */
    public function hasPluralRule()
    {
        return ($this->pluralRule !== null);
    }

    /**
     * Returns a shared default plural rule.
     *
     * @return PluralRule
     */
    public static function getDefaultPluralRule()
    {
        if (static::$defaultPluralRule === null)
            static::$defaultPluralRule = PluralRule::fromString('nplurals=2; plural=n != 1;');

        return static::$defaultPluralRule;
    }

    /**
     * Merge another text domain with the current one.
     *
     * The plural rule of both text domains must be compatible for a successful
     * merge. We are only validating the number of plural forms though, as the
     * same rule could be made up with different expression.
     *
     * @param  TextDomain $textDomain
     * @return TextDomain
     * @throws \RuntimeException
     */
    public function merge(TextDomain $textDomain)
    {
        if ($this->hasPluralRule() && $textDomain->hasPluralRule())
        {
            if ($this->getPluralRule()->getNumPlurals() !== $textDomain->getPluralRule()->getNumPlurals())
                throw new \RuntimeException('Plural rule of merging text domain is not compatible with the current one');
        }
        elseif ($textDomain->hasPluralRule())
            $this->setPluralRule($textDomain->getPluralRule());

        parent::addAll($textDomain->messages);
        return $this;
    }

    /**
     * @return string Serialized version of this TextDomain
     */
    public function serialize()
    {
        return serialize($this->jsonSerialize());
    }

    /**
     * Unserialize data
     * @param string $data Serialized data
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->pluralRule = $data['pluralRule'];
        $this->values = $data['messages'];
    }

    /**
     * Prepare data for JSON serializing - wrap it in an array
     * @return array The 'serialized' version of this TextDomain
     */
    public function jsonSerialize()
    {
        return array(
            'pluralRule' => $this->pluralRule,
            'messages' => $this->values
        );
    }
}
