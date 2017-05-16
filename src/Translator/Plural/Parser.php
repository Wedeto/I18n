<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\Plural\Parser was adapted from Zend\I18n\Translator\Plural\Parser.
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
 * Plural rule parser.
 *
 * This plural rule parser is implemented after the article "Top Down Operator
 * Precedence" described in <http://javascript.crockford.com/tdop/tdop.html>.
 */
class Parser
{
    /**
     * String to parse.
     */
    protected $string;

    /**
     * Current lexer position in the string.
     */
    protected $currentPos;

    /**
     * Current token.
     */
    protected $currentToken;

    /**
     * Table of symbols.
     */
    protected $symbolTable = [];

    /**
     * Create a new plural parser.
     */
    public function __construct()
    {
        $this->populateSymbolTable();
    }

    /**
     * Populate the symbol table.
     */
    protected function populateSymbolTable()
    {
        // Ternary operators
        $this->registerSymbol('?', 20)->setLeftDenotationGetter(
            function (Symbol $self, Symbol $left) {
                $self->first  = $left;
                $self->second = $self->parser->expression();
                $self->parser->advance(':');
                $self->third  = $self->parser->expression();
                return $self;
            }
        );
        $this->registerSymbol(':');

        // Boolean operators
        $this->registerLeftInfixSymbol('||', 30);
        $this->registerLeftInfixSymbol('&&', 40);

        // Equal operators
        $this->registerLeftInfixSymbol('==', 50);
        $this->registerLeftInfixSymbol('!=', 50);

        // Compare operators
        $this->registerLeftInfixSymbol('>', 50);
        $this->registerLeftInfixSymbol('<', 50);
        $this->registerLeftInfixSymbol('>=', 50);
        $this->registerLeftInfixSymbol('<=', 50);

        // Add operators
        $this->registerLeftInfixSymbol('-', 60);
        $this->registerLeftInfixSymbol('+', 60);

        // Multiply operators
        $this->registerLeftInfixSymbol('*', 70);
        $this->registerLeftInfixSymbol('/', 70);
        $this->registerLeftInfixSymbol('%', 70);

        // Not operator
        $this->registerPrefixSymbol('!', 80);

        // Literals
        $this->registerSymbol('n')->setNullDenotationGetter(
            function (Symbol $self) {
                return $self;
            }
        );
        $this->registerSymbol('number')->setNullDenotationGetter(
            function (Symbol $self) {
                return $self;
            }
        );

        // Parentheses
        $this->registerSymbol('(')->setNullDenotationGetter(
            function (Symbol $self) {
                $expression = $self->parser->expression();
                $self->parser->advance(')');
                return $expression;
            }
        );
        $this->registerSymbol(')');

        // EOF
        $this->registerSymbol('eof');
    }

    /**
     * Register a left infix symbol.
     *
     * @param string $id
     * @param int $leftBindingPower
     */
    protected function registerLeftInfixSymbol(string $id, int $leftBindingPower)
    {
        $this->registerSymbol($id, $leftBindingPower)->setLeftDenotationGetter(
            function (Symbol $self, Symbol $left) use ($leftBindingPower) {
                $self->first  = $left;
                $self->second = $self->parser->expression($leftBindingPower);
                return $self;
            }
        );
    }

    /**
     * Register a prefix symbol.
     *
     * @param string $id
     * @param int $leftBindingPower
     */
    protected function registerPrefixSymbol(string $id, int $leftBindingPower)
    {
        $this->registerSymbol($id, $leftBindingPower)->setNullDenotationGetter(
            function (Symbol $self) use ($leftBindingPower) {
                $self->first  = $self->parser->expression($leftBindingPower);
                $self->second = null;
                return $self;
            }
        );
    }

    /**
     * Register a symbol.
     *
     * @param string $id
     * @param int $leftBindingPower
     * @return Wedeto\I18n\Translator\Plural\Symbol
     */
    protected function registerSymbol(string $id, int $leftBindingPower = 0)
    {
        $symbol = new Symbol($this, $id, $leftBindingPower);
        $this->symbolTable[$id] = $symbol;
        return $symbol;
    }

    /**
     * Get a new symbol.
     *
     * @param string $id
     * @return Wedeto\I18n\Translator\Plural\Symbol
     */
    protected function getSymbol(string $id)
    {
        if (!isset($this->symbolTable[$id]))
        {}
            // Unkown symbol exception

        return clone $this->symbolTable[$id];
    }

    /**
     * Parse a string.
     *
     * @param string $string
     * @return Wedeto\I18n\Translator\Plural\Symbol
     */
    public function parse(string $string)
    {
        $this->string = $string . "\0";
        $this->currentPos = 0;
        $this->currentToken = $this->getNextToken();

        return $this->expression();
    }

    /**
     * Parse an expression.
     *
     * @param  int $rightBindingPower
     * @return Wedeto\I18n\Translator\Plural\Symbol
     */
    public function expression(int $rightBindingPower = 0)
    {
        $token = $this->currentToken;
        $this->currentToken = $this->getNextToken();
        $left = $token->getNullDenotation();

        while ($rightBindingPower < $this->currentToken->leftBindingPower)
        {
            $token = $this->currentToken;
            $this->currentToken = $this->getNextToken();
            $left = $token->getLeftDenotation($left);
        }

        return $left;
    }

    /**
     * Advance the current token and optionally check the old token id.
     *
     * @param string $id
     * @throws LogicException
     */
    public function advance($id = null)
    {
        if ($id !== null && $this->currentToken->id !== $id)
        {
            throw new \LogicException(sprintf('Expected token with id %s but received %s', $id, $this->currentToken->id));
        }

        $this->currentToken = $this->getNextToken();
    }

    /**
     * Get the next token.
     *
     * @return array
     * @throws LogicException
     */
    protected function getNextToken()
    {
        while ($this->string[$this->currentPos] === ' ' || $this->string[$this->currentPos] === "\t")
            ++$this->currentPos;

        $result = $this->string[$this->currentPos++];
        $value = null;

        switch ($result)
        {
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                while (ctype_digit($this->string[$this->currentPos]))
                    $result .= $this->string[$this->currentPos++];

                $id    = 'number';
                $value = (int) $result;
                break;

            case '=':
            case '&':
            case '|':
                if ($this->string[$this->currentPos] === $result)
                {
                    $this->currentPos++;
                    $id = $result . $result;
                }
                break;

            case '!':
            case '<':
            case '>':
                if ($this->string[$this->currentPos] === '=')
                {
                    $this->currentPos++;
                    $result .= '=';
                }

                $id = $result;
                break;

            case '*':
            case '/':
            case '%':
            case '+':
            case '-':
            case 'n':
            case '?':
            case ':':
            case '(':
            case ')':
                $id = $result;
                break;

            case ';':
            case "\n":
            case "\0":
                $id = 'eof';
                $this->currentPos--;
                break;

            default:
                throw new \LogicException(sprintf('Found invalid character "%s" in input stream', $result));
        }

        $token = $this->getSymbol($id);
        $token->value = $value;

        return $token;
    }
}
