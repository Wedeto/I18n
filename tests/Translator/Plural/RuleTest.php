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

use PHPUnit\Framework\TestCase;

use LogicException;

/**
 * @covers Wedeto\I18n\Translator\Plural\Rule
 * @covers Wedeto\I18n\Translator\Plural\Parser
 * @covers Wedeto\I18n\Translator\Plural\Symbol
 */
class RuleTest extends TestCase
{
    public static function parseRuleProvider()
    {
        return [
            // Basic calculations
            'addition'         => ['2 + 3', 5],
            'substraction'     => ['3 - 2', 1],
            'multiplication'   => ['2 * 3', 6],
            'division'         => ['6 / 3', 2],
            'integer-division' => ['7 / 4', 1],
            'modulo'           => ['7 % 4', 3],

            // Boolean NOT
            'boolean-not-0'  => ['!0', 1],
            'boolean-not-1'  => ['!1', 0],
            'boolean-not-15' => ['!1', 0],

            // Equal operators
            'equal-true'      => ['5 == 5', 1],
            'equal-false'     => ['5 == 4', 0],
            'not-equal-true'  => ['5 != 5', 0],
            'not-equal-false' => ['5 != 4', 1],

            // Compare operators
            'less-than-true'         => ['5 > 4', 1],
            'less-than-false'        => ['5 > 5', 0],
            'less-or-equal-true'     => ['5 >= 5', 1],
            'less-or-equal-false'    => ['5 >= 6', 0],
            'greater-than-true'      => ['5 < 6', 1],
            'greater-than-false'     => ['5 < 5', 0],
            'greater-or-equal-true'  => ['5 <= 5', 1],
            'greater-or-equal-false' => ['5 <= 4', 0],

            // Boolean operators
            'boolean-and-true'  => ['1 && 1', 1],
            'boolean-and-false' => ['1 && 0', 0],
            'boolean-or-true'   => ['1 || 0', 1],
            'boolean-or-false'  => ['0 || 0', 0],

            // Variable injection
            'variable-injection' => ['n', 0]
        ];
    }

    /**
     * @dataProvider parseRuleProvider
     */
    public function testParseRules($rule, $expectedValue)
    {
        $this->assertEquals(
            $expectedValue,
            Rule::fromString('nplurals=9; plural=' . $rule)->evaluate(0)
        );
    }

    public static function completeRuleProvider()
    {
        // Taken from original gettext tests
        return [
            [
                'n != 1',
                '10111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111'
            ],
            [
                'n>1',
                '00111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111111111111111111111111111111111111111111111'
                . '111111111111111111'
            ],
            [
                'n==1 ? 0 : n==2 ? 1 : 2',
                '20122222222222222222222222222222222222222222222222222222222222'
                . '222222222222222222222222222222222222222222222222222222222222'
                . '222222222222222222222222222222222222222222222222222222222222'
                . '222222222222222222'
            ],
            [
                'n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2',
                '10111111111111111111222222222222222222222222222222222222222222'
                . '222222222222222222222222222222222222222111111111111111111122'
                . '222222222222222222222222222222222222222222222222222222222222'
                . '222222222222222222'
            ],
            [
                'n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2',
                '20111111112222222222201111111120111111112011111111201111111120'
                . '111111112011111111201111111120111111112011111111222222222220'
                . '111111112011111111201111111120111111112011111111201111111120'
                . '111111112011111111'
            ],
            [
                'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2',
                '20111222222222222222201112222220111222222011122222201112222220'
                . '111222222011122222201112222220111222222011122222222222222220'
                . '111222222011122222201112222220111222222011122222201112222220'
                . '111222222011122222'
            ],
            [
                'n%100/10==1 ? 2 : n%10==1 ? 0 : (n+9)%10>3 ? 2 : 1',
                '20111222222222222222201112222220111222222011122222201112222220'
                . '111222222011122222201112222220111222222011122222222222222220'
                . '111222222011122222201112222220111222222011122222201112222220'
                . '111222222011122222'
            ],
            [
                'n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2',
                '20111222222222222222221112222222111222222211122222221112222222'
                . '111222222211122222221112222222111222222211122222222222222222'
                . '111222222211122222221112222222111222222211122222221112222222'
                . '111222222211122222'
            ],
            [
                'n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3',
                '30122333333333333333333333333333333333333333333333333333333333'
                . '333333333333333333333333333333333333333012233333333333333333'
                . '333333333333333333333333333333333333333333333333333333333333'
                . '333333333333333333'
            ],
        ];
    }

    /**
     * @dataProvider completeRuleProvider
     */
    public function testCompleteRules($rule, $expectedValues)
    {
        $rule = Rule::fromString('nplurals=9; plural=' . $rule);
        
        $serialized = serialize($rule);
        $unser = unserialize($serialized);
        $this->assertEquals($unser, $rule);

        for ($i = 0; $i < 200; $i++) {
            $this->assertEquals((int) $expectedValues[$i], $rule->evaluate($i));
        }
    }

    public function testGetNumPlurals()
    {
        $rule = Rule::fromString('nplurals=9; plural=n');
        $this->assertEquals(9, $rule->getNumPlurals());
    }

    public function testInvalidCharacterInRule()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Found invalid character \"#\"");
        $rule = Rule::fromString('nplurals=2; plural=n#1');
    }

    public function testInvalidRule()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected token with id ) but received eof");
        $rule = Rule::fromString('nplurals=2; plural=(n==3');
    }

    public function testRuleWithoutNPlurals()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unknown or invalid parser rule");
        $rule = Rule::fromString('nplura=2; plural=(n==3)');
    }

    public function testRuleWithoutPluralRule()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unknown or invalid parser rule");
        $rule = Rule::fromString('nplurals=2; plura=(n==3)');
    }

    public function testSymbolExceptions()
    {
        $p = new Parser;
        $thrown = false;
        $symb = new Symbol($p, '(', 0);

        try
        {
            $symb->getNullDenotation();
        }
        catch (LogicException $e)
        {
            $this->assertContains("Syntax error", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        $symb = new Symbol($p, '(', 0);

        try
        {
            $symb->getLeftDenotation(null);
        }
        catch (LogicException $e)
        {
            $this->assertContains("Unknown operator", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testRuleWithIncompatibleNumberOfPlurals()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unknown or invalid parser rule");
        $rule = Rule::fromString('nplurals=2; plural=(n==1) ? 1 : (n==2) ? 2 : 0');

        $this->assertEquals(1, $rule->evaluate(1));
        $this->assertEquals(0, $rule->evaluate(0));
        $this->assertEquals(0, $rule->evaluate(3));

        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage("Calculated result 2 is not between 0 and 1");
        $rule->evaluate(2);
    }
}
