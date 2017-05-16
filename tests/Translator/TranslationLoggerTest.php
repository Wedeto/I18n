<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Copyright 2017, Egbert van der Wal

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

namespace Wedeto\I18n\Translator;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\Log\Logger;
use Wedeto\Log\Writer\WriterInterface;
use Wedeto\IO\DirReader;

/**
 * @covers Wedeto\I18n\Translator\TranslationLogger
 */
class TranslationLoggerTest extends TestCase
{
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('logdir'));
        $this->dir = vfsStream::url('logdir');

        $this->writer = new TranslationLogger($this->dir . '/%s_%s.pot');
        $this->writer->setLevel('DEBUG');
        $this->logger = Logger::getLogger(Translator::class);
        $this->logger->removeLogWriters();
        $this->logger->addLogWriter($this->writer);
    }

    public function tearDown()
    {
        $this->logger->removeLogWriters();
    }

    public function getDirContents()
    {
        $dr = new DirReader($this->dir, DirReader::READ_FILE);
        $fl = [];
        foreach ($dr as $entry)
            $fl[] = $entry;
        return $fl;
    }

    public function testWriteLogDirectly()
    {
        // Check that a incorrect string is ignored
        $this->logger->info('Foobar');
        $dr = $this->getDirContents();
        $this->assertEquals(0, count($dr), 'Incorrect message writes files');

        // Check that a incorrect context is ignored
        $this->logger->info('Untranslated message');
        $dr = $this->getDirContents();
        $this->assertEquals(0, count($dr), 'Missing locale writes files');

        $this->logger->info('Untranslated message', ['locale' => 'en']);
        $dr = $this->getDirContents();
        $this->assertEquals(0, count($dr), 'Missing text domain writes files');

        $this->logger->info('Untranslated message', ['locale' => 'en', 'domain' => 'default']);
        $dr = $this->getDirContents();
        $this->assertEquals(0, count($dr), 'Missing msgid writes files');

        $this->logger->info('Untranslated message: foo', ['msgid' => 'foo', 'domain' => 'default', 'locale' => 'en']);
        $dr = $this->getDirContents();
        $this->assertEquals(1, count($dr), 'Correct message and context does not write file');
        $this->assertTrue(file_exists($this->dir . '/default_en.pot'));

        $this->logger->info('Untranslated message: foo', ['msgid' => 'foo', 'msgid_plural' => 'foon', 'domain' => 'default', 'locale' => 'nl']);
        $dr = $this->getDirContents();
        $this->assertEquals(2, count($dr), 'Correct message and context does not write file');
        $this->assertTrue(file_exists($this->dir . '/default_nl.pot'));

        $contents = file_get_contents($this->dir . '/default_nl.pot');
        $this->assertContains('msgid "foo"', $contents);
        $this->assertContains('msgid_plural "foo"', $contents);
        $this->assertContains('msgstr[0]', $contents);
        $this->assertContains('msgstr[1]', $contents);
    }

    public function testWriteLogAfterFailedTranslation()
    {
        $translator = new Translator('en');
        $translator->setLogger($this->logger);

        $this->assertEquals('foo', $translator->translate('foo'));

        $dr = $this->getDirContents();
        $this->assertEquals(1, count($dr), 'Translating unknown message does not write log');
        $this->assertTrue(file_exists($this->dir . '/default_en.pot'));

        $contents = file_get_contents($this->dir . '/default_en.pot');
        $this->assertContains('msgid "foo"', $contents);
    }
}
