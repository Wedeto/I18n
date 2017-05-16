<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

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

namespace Wedeto\I18n\Formatting;

use PHPUnit\Framework\TestCase;
use Wedeto\I18n\Locale;

use InvalidArgumentException;
use Wedeto\I18n\I18nException;

class NumberTest extends TestCase
{
    public function testFormatting()
    {
        $l = new Locale('en_US');
        $fmt = new Number($l);
        $this->assertEquals("1,000,000", $fmt->format(1000000.0));
        $this->assertEquals("1,000", $fmt->format(1000.0));
        $this->assertEquals("3.14", $fmt->format(3.14));

        $this->assertEquals("15", $fmt->format(15, 0));
        $this->assertEquals("3.1415", $fmt->format(3.1415));
        $this->assertEquals("3.14", $fmt->format(3.1415, 2));

        $this->assertEquals(',', $fmt->getGroupingSymbol());
        $this->assertEquals('.', $fmt->getDecimalSymbol());
        $this->assertEquals($l, $fmt->getLocale());

        $l = new Locale('nl_NL');
        $fmt = new Number($l);
        $this->assertEquals("1.000.000", $fmt->format(1000000.0));
        $this->assertEquals("1.000", $fmt->format(1000.0));
        $this->assertEquals("3,14", $fmt->format(3.14));

        $this->assertEquals("15", $fmt->format(15, 0));
        $this->assertEquals("3,1415", $fmt->format(3.1415));
        $this->assertEquals("3,14", $fmt->format(3.1415, 2));

        $this->assertEquals('.', $fmt->getGroupingSymbol());
        $this->assertEquals(',', $fmt->getDecimalSymbol());
        $this->assertEquals($l, $fmt->getLocale());
    }

    public function testParsing()
    {
        $l = new Locale('en_US');
        $fmt = new Number($l);

        $this->assertEquals(3.14, $fmt->parse("3.14"));
        $this->assertEquals(1000, $fmt->parse("1,000"));
        $this->assertEquals(1000, $fmt->parse("1000"));
        $this->assertEquals(1000000, $fmt->parse("1000000"));
        $this->assertEquals(1000000, $fmt->parse("1000000"));
        $this->assertEquals($l, $fmt->getLocale());

        $l = new Locale('nl_NL');
        $fmt = new Number($l);

        $this->assertEquals(3.14, $fmt->parse("3,14"));
        $this->assertEquals(1000, $fmt->parse("1.000"));
        $this->assertEquals(1000, $fmt->parse("1000"));
        $this->assertEquals(1000000, $fmt->parse("1.000.000"));
        $this->assertEquals(1000000, $fmt->parse("1000000"));
        $this->assertEquals($l, $fmt->getLocale());
    }

    public function testChangeDecimalPrecision()
    {
        $l = new Locale('nl_NL');
        $fmt = new Number($l);

        $this->assertEquals($fmt, $fmt->setDecimalPrecision(2));
        $this->assertEquals('3,14', $fmt->format(M_PI));
        $this->assertEquals($fmt, $fmt->setDecimalPrecision(4));
        $this->assertEquals('3,1416', $fmt->format(M_PI));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Decimals should be an in");
        $fmt->format(M_PI, 'foo');
    }
}
