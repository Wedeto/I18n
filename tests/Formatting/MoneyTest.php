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

class MoneyTest extends TestCase
{
    public function testFormatting()
    {
        $l = new Locale('en_US');
        $fmt = new Money($l);
        $this->assertEquals("$100.00", $fmt->format(100));
        $this->assertEquals("$100,000.00", $fmt->format(100000));
        $this->assertEquals("€100,000.00", $fmt->format(100000, "EUR"));
        $this->assertEquals("RUB100,000.00", $fmt->format(100000, "RUB"));
        $this->assertEquals("$", $fmt->getCurrencySymbol());
        $this->assertEquals("USD", $fmt->getCurrencyCode());
        $this->assertEquals($l, $fmt->getLocale());

        $l = new Locale('nl_NL');
        $fmt = new Money($l);
        $this->assertEquals("€ 100,00", $fmt->format(100));
        $this->assertEquals("€ 100.000,00", $fmt->format(100000));
        $this->assertEquals('US$ 100.000,00', $fmt->format(100000, "USD"));
        $this->assertEquals('RUB 100.000,00', $fmt->format(100000, "RUB"));
        $this->assertEquals("€", $fmt->getCurrencySymbol());
        $this->assertEquals("EUR", $fmt->getCurrencyCode());
        $this->assertEquals($l, $fmt->getLocale());

        $l = new Locale('ru_RU');
        $fmt = new Money($l);
        $this->assertEquals("100,00 руб.", $fmt->format(100));
        $this->assertEquals("100 000,00 руб.", $fmt->format(100000));
        $this->assertEquals('100 000,00 $', $fmt->format(100000, "USD"));
        $this->assertEquals('100 000,00 €', $fmt->format(100000, "EUR"));
        $this->assertEquals("руб.", $fmt->getCurrencySymbol());
        $this->assertEquals("RUB", $fmt->getCurrencyCode());

        $this->expectException(I18nException::class);
        $this->expectExceptionMessage("Invalid currency: €");
        $fmt->format(100000, "€");
    }

    public function testParsing()
    {
        $l = new Locale('en_US');
        $fmt = new Money($l);

        $this->assertTrue(100.0 === $fmt->parse("$100.00"));
        $this->assertTrue(100.0 === $fmt->parse("€100.00", "EUR"));
        $this->assertTrue(100.0 === $fmt->parse("RUB100.00", "RUB"));
        $this->assertEquals($l, $fmt->getLocale());

        $l = new Locale('nl_NL');
        $fmt = new Money($l);
        $this->assertTrue(100.0 === $fmt->parse('US$ 100,00', "USD"));
        $this->assertTrue(100.0 === $fmt->parse('100,00', "USD"));

        $this->assertTrue(100.0 === $fmt->parse('€ 100,00'));
        $this->assertTrue(100.0 === $fmt->parse('RUB 100,00', "RUB"));
        $this->assertEquals($l, $fmt->getLocale());

        $this->expectException(I18nException::class);
        $this->expectExceptionMessage("Cannot parse value");
        $fmt->parse("foo bar");
    }

    public function testFallbackParser()
    {
        $mocker = $this->prophesize(Money::class);
        $mocker->parse("foo bar")->willReturn(42.0)->shouldBeCalled();

        $mock = $mocker->reveal();

        $l = new Locale('en_US');
        $fmt = new Money($l);
        $fmt->setFallbackParser($mock);

        $this->assertEquals(42, $fmt->parse("foo bar"));

        $fmt->setFallbackParser(null);
        $thrown = false;
        try
        {
            $this->assertEquals(42, $fmt->parse("foo bar"));
        }
        catch (I18nException $e)
        {
            $this->assertContains("Cannot parse value", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parser must have a parse method");
        $fmt->setFallbackParser("foo");
    }
}
