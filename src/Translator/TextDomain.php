<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\Translator was adapted from
Zend\I18n\Translator\Translator.
The modifications are: Copyright 2017, Egbert van der Wal.

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

use JsonSerializable;

use Wedeto\Util\Dictionary;
use Wedeto\I18n\Translator\Plural\Rule as PluralRule;

/**
 * Text domain.
 */
class TextDomain extends Dictionary
{
    /** Plural rule */
    protected $plural_rule;

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
        $this->plural_rule = $rule;
        return $this;
    }

    /**
     * Get the plural rule.
     *
     * @param  bool $fallbackToDefaultRule
     * @return PluralRule|null
     */
    public function getPluralRule(bool $fallbackToDefaultRule = true)
    {
        if ($this->plural_rule === null && $fallbackToDefaultRule)
            return static::getDefaultPluralRule();

        return $this->plural_rule;
    }

    /**
     * Checks whether the text domain has a plural rule.
     *
     * @return bool
     */
    public function hasPluralRule()
    {
        return $this->plural_rule !== null;
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
        $this->plural_rule = $data['plural_rule'];
        $this->values = $data['messages'];
    }

    /**
     * Prepare data for JSON serializing - wrap it in an array
     * @return array The 'serialized' version of this TextDomain
     */
    public function jsonSerialize()
    {
        return array(
            'plural_rule' => $this->plural_rule,
            'messages' => $this->values
        );
    }
}
