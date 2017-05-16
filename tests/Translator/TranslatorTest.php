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

use PHPUnit\Framework\TestCase;
use Locale as PHPLocale;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\Util\Cache;
use Wedeto\I18n\Translator\Plural\Rule as PluralRule;
use Wedeto\I18n\Locale;

class TranslatorTest extends TestCase
{
    protected $translator;
    protected $originalLocale;
    protected $testFilesDir;

    public function setUp()
    {
        PHPLocale::setDefault('en_EN');
        $this->originalLocale = PHPLocale::getDefault();
        $this->translator = new Translator();

        $this->testFilesDir = __DIR__ . '/resources';
    }

    public function tearDown()
    {
        if (extension_loaded('intl'))
            PHPLocale::setDefault($this->originalLocale);
    }

    public function testDefaultLocale()
    {
        $this->assertEquals('en_EN', $this->translator->getLocale()->getLocale());
    }

    public function testForcedLocale()
    {
        $this->translator->setLocale(new Locale('de_DE'));
        $this->assertEquals('de_DE', $this->translator->getLocale()->getLocale());
    }

    public function testTranslate()
    {
        $td = new TextDomain(['foo' => 'bar']);
        $this->translator->injectMessages('default', $td, 'en_EN');
        $this->translator->setLocale(new Locale('en_EN'));
        $this->assertEquals('bar', $this->translator->translate('foo'));
    }

    public function getSampleTextDomainWithPlurals()
    {
        $td = new TextDomain([
            'Message 1' => 'Message 1 (en)',
            'Message 2' => 'Message 2 (en)',
            'Message 3' => 'Message 3 (en)',
            'Message 4' => 'Message 4 (en)',
            'Message 5' => array(
                0 => 'Message 5 (en) Plural 0',
                1 => 'Message 5 (en) Plural 1',
                2 => 'Message 5 (en) Plural 2'
            ),
            'Cooking furniture' => 'Küchen Möbel (en)',
            'Küchen Möbel' => 'Cooking furniture (en)'
        ]);

        $td->setPluralRule(
            PluralRule::fromString(
                'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);'
            )
        );

        return $td;
    }

    public function getSampleTextDomainWithoutPlurals()
    {
        $td = new TextDomain([
            'Message 9' => 'Message 9 (ja)',
        ]);

        $td->setPluralRule(PluralRule::fromString('nplurals=1; plural=0;'));
        return $td;
    }

    public function testTranslatePlurals()
    {
        $this->translator->setLocale(new Locale('en_EN'));
        $this->translator->injectMessages('default', $this->getSampleTextDomainWithPlurals(), 'en_EN');

        $pl0 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 1);
        $pl1 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 2);
        $pl2 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 10);

        $this->assertEquals('Message 5 (en) Plural 0', $pl0);
        $this->assertEquals('Message 5 (en) Plural 1', $pl1);
        $this->assertEquals('Message 5 (en) Plural 2', $pl2);
    }

    public function testTranslateNoPlurals()
    {
        // Some languages such as Japanese and Chinese does not have plural forms
        $this->translator->setLocale(new Locale('ja_JP'));
        $this->translator->injectMessages('default', $this->getSampleTextDomainWithoutPlurals(), 'ja_JP');

        $pl0 = $this->translator->translatePlural('Message 9', 'Message 9 Plural', 1);
        $pl1 = $this->translator->translatePlural('Message 9', 'Message 9 Plural', 2);
        $pl2 = $this->translator->translatePlural('Message 9', 'Message 9 Plural', 10);

        $this->assertEquals('Message 9 (ja)', $pl0);
        $this->assertEquals('Message 9 (ja)', $pl1);
        $this->assertEquals('Message 9 (ja)', $pl2);
    }

    public function testTranslateNonExistantLocale()
    {
        // Test that a locale without translations does not cause warnings
        $this->translator->setLocale(new Locale('es_ES'));
        $this->assertEquals('Message 1', $this->translator->translate('Message 1'));
        $this->assertEquals('Message 9', $this->translator->translate('Message 9'));

        $this->translator->setLocale(new Locale('fr_FR'));
        $this->assertEquals('Message 1', $this->translator->translate('Message 1'));
        $this->assertEquals('Message 9', $this->translator->translate('Message 9'));
    }

    public function testGetAllMessagesLoadedInTranslator()
    {
        $this->translator->setLocale(new Locale('en_EN'));
        $this->translator->injectMessages('default', $this->getSampleTextDomainWithPlurals(), 'en_EN');

        $allMessages = $this->translator->getAllMessages();
        $this->assertInstanceOf(TextDomain::class, $allMessages);
        $this->assertEquals(7, count($allMessages));
        $this->assertEquals('Message 1 (en)', $allMessages['Message 1']);
    }

    public function testGetAllMessagesReturnsEmptyTextDomainWhenGivenTextDomainIsNotFound()
    {
        $this->translator->setLocale(new Locale('en_EN'));
        $this->translator->injectMessages('default', $this->getSampleTextDomainWithPlurals(), 'en_EN');

        $allMessages = $this->translator->getAllMessages('foo_domain');
        $this->assertEquals(0, count($allMessages));
    }

    public function testGetAllMessagesReturnsEmptyTextDomainWhenGivenLocaleDoesNotExist()
    {
        $this->translator->setLocale(new Locale('en_EN'));
        $this->translator->injectMessages('default', $this->getSampleTextDomainWithPlurals(), 'en_EN');

        $allMessages = $this->translator->getAllMessages('default', new Locale('es_ES'));
        $this->assertEquals(0, count($allMessages));
    }

    public function testUseCache()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('cachedir'));
        $this->dir = vfsStream::url('cachedir');

        Cache::setCachePath($this->dir);
        $c = new Cache("translatecache");

        $this->translator->setCache($c);
        $this->translator->setLocale(new Locale('en_EN'));
        $td = new TextDomain(['foo' => 'bar']);
        $this->translator->injectMessages('default', $td, 'en_EN');

        // Check that the translation works
        $this->assertEquals('bar', $this->translator->translate('foo'));

        // Create a new translator
        $trl = new Translator;
        $trl->setLocale(new Locale('en_EN'));

        // Check that the translation doesn't work
        $this->assertEquals('foo', $trl->translate('foo'));

        // And it does after setting the cache
        $trl->setCache($c);
        $this->assertEquals('bar', $trl->translate('foo'));
    }

    public function testSetTextDomain()
    {
        $td = new TextDomain(['foo' => 'bar']);
        $this->translator->injectMessages('foobar', $td, 'en_EN');

        // Incorrect TextDomain, so it shouldn't translate
        $this->assertEquals('foo', $this->translator->translate('foo'));

        $this->translator->setTextDomain('foobar');

        // Now it should work
        $this->assertEquals('bar', $this->translator->translate('foo'));
    }

    public function testFallbackLocale()
    {
        $td = new TextDomain(['foo' => 'bar']);
        $this->translator->injectMessages('default', $td, 'en_US');

        // Incorrect Locale, so it shouldn't translate
        $this->translator->setLocale(new Locale('en_EN'));
        $this->assertEquals('foo', $this->translator->translate('foo'));
        $this->assertNull($this->translator->getFallbackLocale());

        $fbl = new Locale('en_US');
        $this->assertEquals($this->translator, $this->translator->setFallbackLocale($fbl));
        $this->assertEquals($fbl, $this->translator->getFallbackLocale());
            

        // Now it should work
        $this->assertEquals('bar', $this->translator->translate('foo'));

        // And break it again
        $this->assertEquals($this->translator, $this->translator->setFallbackLocale(null));
        $this->assertEquals('foo', $this->translator->translate('foo'));
        $this->assertNull($this->translator->getFallbackLocale());

        // Try if passing a non-locale gives an error
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid locale");
        $this->translator->setFallbackLocale('foo');
    }

    public function testIgnoreTranslation()
    {
        $td = new TextDomain([
            'foo' => 'bar',
            'test' => [
                0 => 'foobar',
                1 => 'foobarbaz',
                2 => 'foobarbar'
            ]
        ]);
        $td->setPluralRule(
            PluralRule::fromString(
                'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);'
            )
        );

        $this->translator->injectMessages('default', $td, 'en_EN');

        // It should translate
        $this->assertEquals('bar', $this->translator->translate('foo'));
        $this->assertEquals('foobar', $this->translator->translatePlural('test', 'testmult', 1));
        $this->assertEquals('foobarbaz', $this->translator->translatePlural('test', 'testmult', 2));
        $this->assertEquals('foobarbar', $this->translator->translatePlural('test', 'testmult', 10));

        // C should return unmodified string always
        $this->translator->setLocale(new Locale('C'));
        $this->assertEquals('foo', $this->translator->translate('foo'));
        $this->assertEquals('test', $this->translator->translatePlural('test', 'testmult', 1));
        $this->assertEquals('testmult', $this->translator->translatePlural('test', 'testmult', 2));
        $this->assertEquals('testmult', $this->translator->translatePlural('test', 'testmult', 10));
    }

    public function testPatterns()
    {
        $this->translator->addPattern($this->testFilesDir . '/testmo', 'translation-%s.mo', 'default');
        $this->translator->setLocale(new Locale('en_US'));

        $pl0 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 1);
        $pl1 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 2);
        $pl2 = $this->translator->translatePlural('Message 5', 'Message 5 Plural', 10);

        $this->assertEquals('Message 5 (en) Plural 0', $pl0);
        $this->assertEquals('Message 5 (en) Plural 1', $pl1);
        $this->assertEquals('Message 5 (en) Plural 2', $pl2);

        $this->translator->setLocale(new Locale('de_DE'));

        $m0 = $this->translator->translate('Message 1');
        $m1 = $this->translator->translate('Message 8');

        $this->assertEquals('Nachricht 1', $m0);
        $this->assertEquals('Nachricht 8', $m1);
    }

    public function testTranslateEmptyMessages()
    {
        $this->assertEquals('', $this->translator->translate(''));
        $this->assertEquals('', $this->translator->translatePlural('', '', 3));
    }

    public function testInjectMessages()
    {
        $tdm1 = new TextDomain(['foo' => 'bar']);
        $tdm2 = new TextDomain(['foobar' => 'foobaz']);

        $this->translator->setLocale('en_US');
        $this->assertEquals($this->translator, $this->translator->injectMessages('default', $tdm1, 'en_US'));
        $this->assertEquals('bar', $this->translator->translate('foo'));
        $this->assertEquals('foobar', $this->translator->translate('foobar'));

        $this->assertEquals($this->translator, $this->translator->injectMessages('default', $tdm2, 'en_US'));
        $this->assertEquals('bar', $this->translator->translate('foo'));
        $this->assertEquals('foobaz', $this->translator->translate('foobar'));
    }

    public function testInvalidPlural()
    {
        $tdm = $this->getSampleTextDomainWithPlurals();

        $this->translator->injectMessages('default', $tdm, 'en_US');
        $this->translator->setLocale('en_US');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Provided index 1 does not exist in plural array');
        $this->translator->translatePlural('Message 4', 'Message4n', 2);
    }

    public function testLoadMessagesSkipDirs()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('translations'));
        $dir = vfsStream::url('translations');

        $mo_file = file_get_contents($this->testFilesDir . '/translation_en.mo');
        file_put_contents($dir . '/nl.mo', $mo_file);
        file_put_contents($dir . '/en.mo', $mo_file);
        mkdir($dir . '/de.mo');
    
        $this->translator->addPattern($dir, '%s.mo', 'default');

        $this->translator->setLocale('en');
        $this->assertEquals('Message 1 (en)', $this->translator->translate('Message 1'));

        $this->translator->setLocale('nl');
        $this->assertEquals('Message 1 (en)', $this->translator->translate('Message 1'));

        $this->translator->setLocale('de');
        $this->assertEquals('Message 1', $this->translator->translate('Message 1'));
    }
}
