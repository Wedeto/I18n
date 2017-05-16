<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\Plural\Rule was adapted from Zend\I18n\Translator\Plural\Rule.
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

/**
 * Plural rule evaluator.
 */
class Rule implements \Serializable
{
    /**
     * Parser instance.
     */
    protected static $parser;

    /**
     * Abstract syntax tree.
     */
    protected $ast;

    /**
     * Number of plurals in this rule.
     */
    protected $numPlurals;

    /**
     * Create a new plural rule.
     *
     * @param int $numPlurals
     * @param array $ast
     * @return Wedeto\I18n\Translator\Plural\Rule
     */
    protected function __construct(int $numPlurals, array $ast)
    {
        $this->numPlurals = $numPlurals;
        $this->ast = $ast;
    }

    /**
     * Evaluate a number and return the plural index.
     *
     * @param int $number
     * @return int
     * @throws OutOfRangeException
     */
    public function evaluate(int $number)
    {
        $result = $this->evaluateASTPart($this->ast, abs((int) $number));

        if ($result < 0 || $result >= $this->numPlurals)
        {
            throw new \OutOfRangeException(
                sprintf('Calculated result %s is not between 0 and %d', $result, ($this->numPlurals - 1))
            );
        }

        return $result;
    }

    public function serialize()
    {
        $data = array(
            'numPlurals' => $this->numPlurals,
            'ast' => $this->ast
        );
        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->numPlurals = $data['numPlurals'];
        $this->ast = $data['ast'];
    }

    /**
     * Get number of possible plural forms.
     *
     * @return int
     */
    public function getNumPlurals()
    {
        return $this->numPlurals;
    }

    /**
     * Evaluate a part of an ast.
     *
     * @param  array $ast
     * @param  int $number
     * @return int
     * @throws LogicException When an unknown symbol is encountered
     */
    protected function evaluateASTPart(array $ast, int $number)
    {
        switch ($ast['id'])
        {
            case 'number':
                return $ast['arguments'][0];

            case 'n':
                return $number;

            case '+':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       + $this->evaluateASTPart($ast['arguments'][1], $number);

            case '-':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       - $this->evaluateASTPart($ast['arguments'][1], $number);

            case '/':
                // Integer division
                return floor(
                    $this->evaluateASTPart($ast['arguments'][0], $number)
                    / $this->evaluateASTPart($ast['arguments'][1], $number)
                );

            case '*':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       * $this->evaluateASTPart($ast['arguments'][1], $number);

            case '%':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       % $this->evaluateASTPart($ast['arguments'][1], $number);

            case '>':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       > $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '>=':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       >= $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '<':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       < $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '<=':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       <= $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '==':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       == $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '!=':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       != $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '&&':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       && $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '||':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       || $this->evaluateASTPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '!':
                return !$this->evaluateASTPart($ast['arguments'][0], $number)
                       ? 1 : 0;

            case '?':
                return $this->evaluateASTPart($ast['arguments'][0], $number)
                       ? $this->evaluateASTPart($ast['arguments'][1], $number)
                       : $this->evaluateASTPart($ast['arguments'][2], $number);

            default:
                // @codeCoverageIgnoreStart
                // Unknown symbol, but the parser will catch it so it is a fallback
                // whenever new symbols might be added.
                throw new \LogicException(sprintf('Unknown token: %s', $ast['id']));
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Create a new rule from a string.
     *
     * @param string $string
     * @throws LogicException
     * @return Wedeto\I18n\Translator\Plural\Rule
     */
    public static function fromString(string $string)
    {
        if (static::$parser === null)
            static::$parser = new Parser();

        if (!preg_match('(nplurals=(?P<nplurals>\d+))', $string, $match))
            throw new \LogicException(sprintf('Unknown or invalid parser rule: %s', $string));

        $numPlurals = (int)$match['nplurals'];

        if (!preg_match('(plural=(?P<plural>[^;\n]+))', $string, $match))
            throw new \LogicException(sprintf('Unknown or invalid parser rule: %s', $string));

        $tree = static::$parser->parse($match['plural']);
        $ast  = static::createAST($tree);

        return new static($numPlurals, $ast);
    }

    /**
     * Create an AST from a tree.
     *
     * Theoretically we could just use the given Symbol, but that one is not
     * so easy to serialize and also takes up more memory.
     *
     * @param Wedeto\I18n\Translator\Plural\Symbol $symbol
     * @return array
     */
    protected static function createAST(Symbol $symbol)
    {
        $ast = array('id' => $symbol->id, 'arguments' => []);

        switch ($symbol->id)
        {
            case 'n':
                break;

            case 'number':
                $ast['arguments'][] = $symbol->value;
                break;

            case '!':
                $ast['arguments'][] = static::createAST($symbol->first);
                break;

            case '?':
                $ast['arguments'][] = static::createAST($symbol->first);
                $ast['arguments'][] = static::createAST($symbol->second);
                $ast['arguments'][] = static::createAST($symbol->third);
                break;

            default:
                $ast['arguments'][] = static::createAST($symbol->first);
                $ast['arguments'][] = static::createAST($symbol->second);
                break;
        }

        return $ast;
    }
}
