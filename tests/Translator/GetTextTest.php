<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\GetText was adapted from Zend\I18n\Translator\Loader\Gettext.
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
use Locale;
use InvalidArgumentException;

/**
 * @covers Wedeto\I18n\Translator\GetText
 */
class GetTextTest extends TestCase
{
    protected $testFilesDir;
    protected $originalLocale;
    protected $originalIncludePath;

    public function setUp()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $this->originalLocale = Locale::getDefault();
        Locale::setDefault('en_EN');

        $this->testFilesDir = realpath(__DIR__ . '/resources');

        $this->originalIncludePath = get_include_path();
        set_include_path($this->testFilesDir . PATH_SEPARATOR . $this->testFilesDir . '/translations.phar');
    }

    public function tearDown()
    {
        if (extension_loaded('intl')) {
            Locale::setDefault($this->originalLocale);
            set_include_path($this->originalIncludePath);
        }
    }

    public function testLoaderFailsToLoadMissingFile()
    {
        $loader = new GetText();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find or open file');
        $loader->load('en_EN', 'missing');
    }

    public function testLoaderFailsToLoadBadFile()
    {
        $loader = new GetText();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid gettext file');
        $loader->load('en_EN', $this->testFilesDir . '/failed.mo');
    }

    public function testLoaderLoadsEmptyFile()
    {
        $loader = new GetText();
        $domain = $loader->load('en_EN', $this->testFilesDir . '/translation_empty.mo');
        $this->assertInstanceOf(TextDomain::class, $domain);
    }

    public function testLoaderLoadsBigEndianFile()
    {
        $loader = new GetText();
        $domain = $loader->load('en_EN', $this->testFilesDir . '/translation_bigendian.mo');
        $this->assertInstanceOf(TextDomain::class, $domain);
    }

    public function testLoaderReturnsValidTextDomain()
    {
        $loader = new GetText();
        $textDomain = $loader->load('en_EN', $this->testFilesDir . '/translation_en.mo');

        $this->assertEquals('Message 1 (en)', $textDomain['Message 1']);
        $this->assertEquals('Message 4 (en)', $textDomain['Message 4']);
    }

    public function testLoaderLoadsPluralRules()
    {
        $loader     = new GetText();
        $textDomain = $loader->load('en_EN', $this->testFilesDir . '/translation_en.mo');

        $this->assertEquals(2, $textDomain->getPluralRule()->evaluate(0));
        $this->assertEquals(0, $textDomain->getPluralRule()->evaluate(1));
        $this->assertEquals(1, $textDomain->getPluralRule()->evaluate(2));
        $this->assertEquals(2, $textDomain->getPluralRule()->evaluate(10));
    }

    public function testLoaderLoadsFromPhar()
    {
        $loader = new GetText();
        $textDomain = $loader->load('en_EN', 'phar://' . $this->testFilesDir . '/translations.phar/translation_en.mo');

        $this->assertEquals('Message 1 (en)', $textDomain['Message 1']);
        $this->assertEquals('Message 4 (en)', $textDomain['Message 4']);
    }

    public function testLoaderLoadsPlural()
    {
        $loader = new GetText();

        $textDomain = $loader->load('en_EN', $this->testFilesDir . '/translation_en.mo');

        $this->assertEquals(
            [
                'Message A (en) Plural 0',
                'Message A (en) Plural 1',
                'Message A (en) Plural 2',
            ],
            $textDomain['Message A']->toArray()
        );

        $this->assertEquals(
            [
                'Message B (en) Plural 0',
                'Message B (en) Plural 1',
                'Message B (en) Plural 2',
            ],
            $textDomain['Message B']->toArray()
        );
    }

    public function testLoaderRefusesUnknownRevision()
    {
        $loader = new GetText();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has an unknown major revision');
        $textDomain = $loader->load('en_EN', $this->testFilesDir . '/corrupt.mo');
    }
}
