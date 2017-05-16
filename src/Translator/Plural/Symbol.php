<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\Plural\Symbol was adapted from Zend\I18n\Translator\Plural\Symbol.
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

namespace Wedeto\I18n\Translator\Plural;

use LogicException;

/**
 * Parser symbol.
 *
 * All properties in the symbol are defined as public for easier and faster
 * access from the applied closures. An exception are the closure properties
 * themselves, as they have to be accessed via the appropriate getter and
 * setter methods.
 */
class Symbol
{
    /**
     * Parser instance.
     */
    public $parser;

    /**
     * Node or token type name.
     */
    public $id;

    /**
     * Left binding power (precedence).
     */
    public $leftBindingPower;

    /**
     * Getter for null denotation.
     */
    protected $nullDenotationGetter;

    /**
     * Getter for left denotation.
     */
    protected $leftDenotationGetter;

    /**
     * Value used by literals.
     */
    public $value;

    /**
     * First node value.
     */
    public $first;

    /**
     * Second node value.
     */
    public $second;

    /**
     * Third node value.
     */
    public $third;

    /**
     * Create a new symbol.
     *
     * @param Parser $parser
     * @param string $id
     * @param int $leftBindingPower
     */
    public function __construct(Parser $parser, string $id, int $leftBindingPower)
    {
        $this->parser = $parser;
        $this->id = $id;
        $this->leftBindingPower = $leftBindingPower;
    }

    /**
     * Set the null denotation getter.
     *
     * @param callable $getter
     * @return Symbol
     */
    public function setNullDenotationGetter(callable $getter)
    {
        $this->nullDenotationGetter = $getter;
        return $this;
    }

    /**
     * Set the left denotation getter.
     *
     * @param callable $getter
     * @return Symbol
     */
    public function setLeftDenotationGetter(callable $getter)
    {
        $this->leftDenotationGetter = $getter;
        return $this;
    }

    /**
     * Get null denotation.
     *
     * @throws LogicException
     * @return Symbol
     */
    public function getNullDenotation()
    {
        if ($this->nullDenotationGetter === null)
            throw new \LogicException(sprintf('Syntax error: %s', $this->id));

        $function = $this->nullDenotationGetter;
        return $function($this);
    }

    /**
     * Get left denotation.
     *
     * @param  Symbol $left
     * @throws LogicException
     * @return Symbol
     */
    public function getLeftDenotation($left)
    {
        if ($this->leftDenotationGetter === null)
            throw new LogicException(sprintf('Unknown operator: %s', $this->id));

        $function = $this->leftDenotationGetter;
        return $function($this, $left);
    }
}
