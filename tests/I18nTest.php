<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Copyright 2017, Egbert van der Wal <wedeto at pointpro dot nl>

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

namespace Wedeto\I18n;

use PHPUnit\Framework\TestCase;

use Wedeto\I18n\Translator\TextDomain;

class I18nTest extends TestCase
{
    public function setUp()
    {
        $this->l_en = new Locale('en_US');
        $this->l_nl = new Locale('nl_NL');
        $this->i18n = new I18n($this->l_en);
        I18n::setDefault($this->i18n);

        $this->translator = $this->i18n->getTranslator();

        $td = new TextDomain([
            'foo' => 'foo (en_US)',
            'foo n' => [
                'foo (en_US) singular',
                'foo (en_US) plural'
            ]
        ]);
        $this->translator->injectMessages('default', $td, 'en_US');

        $td = new TextDomain([
            'foo' => 'foo2 (en_US)',
            'foo n' => [
                'foo2 (en_US) singular',
                'foo2 (en_US) plural'
            ]
        ]);
        $this->translator->injectMessages('test', $td, 'en_US');

        $td = new TextDomain([
            'foo' => 'foo (nl_NL)',
            'foo n' => [
                'foo (nl_NL) singular',
                'foo (nl_NL) plural'
            ]
        ]);
        $this->translator->injectMessages('default', $td, 'nl_NL');

        $td = new TextDomain([
            'foo' => 'foo2 (nl_NL)',
            'foo n' => [
                'foo2 (nl_NL) singular',
                'foo2 (nl_NL) plural'
            ]
        ]);
        $this->translator->injectMessages('test', $td, 'nl_NL');
    }

    public function testTranslations()
    {
        $this->i18n->setLocale('en_US');
        $this->assertEquals('foo (en_US)', $this->i18n->translate('foo'));
        $this->assertEquals('foo2 (en_US)', $this->i18n->translate('foo', 'test'));

        $this->assertEquals('foo (en_US) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1));
        $this->assertEquals('foo (en_US) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2));
        $this->assertEquals('foo2 (en_US) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1, 'test'));
        $this->assertEquals('foo2 (en_US) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2, 'test'));

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals('foo (nl_NL)', $this->i18n->translate('foo'));
        $this->assertEquals('foo2 (nl_NL)', $this->i18n->translate('foo', 'test'));

        $this->assertEquals('foo (nl_NL) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1));
        $this->assertEquals('foo (nl_NL) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2));
        $this->assertEquals('foo2 (nl_NL) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1, 'test'));
        $this->assertEquals('foo2 (nl_NL) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2, 'test'));
    }

    public function testTranslationsWithTextDomain()
    {
        $this->i18n->setTextDomain('test');
        $this->i18n->setLocale('en_US');
        $this->assertEquals('foo2 (en_US)', $this->i18n->translate('foo'));
        $this->assertEquals('foo2 (en_US) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1));
        $this->assertEquals('foo2 (en_US) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2));

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals('foo2 (nl_NL)', $this->i18n->translate('foo'));
        $this->assertEquals('foo2 (nl_NL) singular', $this->i18n->translatePlural('foo n', 'foo nn', 1));
        $this->assertEquals('foo2 (nl_NL) plural', $this->i18n->translatePlural('foo n', 'foo nn', 2));
    }

    public function testTranslationList()
    {
        $this->i18n->setLocale('en_US');
        $this->assertEquals(
            'foo english',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );

        $this->i18n->setLocale('en');
        $this->assertEquals(
            'foo english',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );

        $this->i18n->setLocale('fr');
        $this->assertEquals(
            'foo english',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals(
            'foo dutch',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );

        $this->i18n->setLocale('nl');
        $this->assertEquals(
            'foo dutch',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );

        $this->i18n->setLocale('nl_NL@currency=NLG');
        $this->assertEquals(
            'foo dutch',
            $this->i18n->translateList(['en' => 'foo english', 'nl' => 'foo dutch'])
        );
    }

    public function testLocalizeNumbers()
    {
        $this->i18n->setLocale('en_US');
        $this->assertEquals("3.14", $this->i18n->getNumberFormatter()->format(3.14));
        $this->assertEquals("1,234,567", $this->i18n->getNumberFormatter()->format(1234567));

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals("3,14", $this->i18n->getNumberFormatter()->format(3.14));
        $this->assertEquals("1.234.567", $this->i18n->getNumberFormatter()->format(1234567));
    }

    public function testLocalizeMoney()
    {
        $this->i18n->setLocale('en_US');
        $this->assertEquals('$29.99', $this->i18n->getMoneyFormatter()->format(29.99));
        $this->assertEquals('$29.99', $this->i18n->getMoneyFormatter()->format(29.9879));

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals('€ 29,99', $this->i18n->getMoneyFormatter()->format(29.99));
        $this->assertEquals('€ 29,99', $this->i18n->getMoneyFormatter()->format(29.9879));
    }

    public function testLocalizeMessage()
    {
        $this->i18n->setLocale('en_US');
        $this->assertEquals(
            'More than 1,000 people pay $29.99 for this', 
            $this->i18n->formatMessage(
                'More than {people:i} people pay {cost:c} for this',
                ['people' => 1000, 'cost' => 29.99]
            )
        );

        $this->i18n->setLocale('nl_NL');
        $this->assertEquals(
            'Meer dan 1.000 mensen betalen hier € 29,99 voor', 
            $this->i18n->formatMessage(
                'Meer dan {people:i} mensen betalen hier {cost:c} voor',
                ['people' => 1000, 'cost' => 29.99]
            )
        );
    }
}

