<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\I18n\Translator\TextDomain was adapted from
Zend\I18n\Translator\TextDomain.
The modifications are: Copyright 2017, Egbert van der Wal.

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
use Wedeto\I18n\Translator\Plural\Rule as PluralRule;
use RuntimeException;

class TextDomainTest extends TestCase
{
    public function testInstantiation()
    {
        $domain = new TextDomain(['foo' => 'bar']);
        $this->assertEquals('bar', $domain['foo']);
    }

    public function testArrayAccess()
    {
        $domain = new TextDomain();
        $domain['foo'] = 'bar';
        $this->assertEquals('bar', $domain['foo']);
    }

    public function testPluralRuleSetter()
    {
        $domain = new TextDomain();
        $domain->setPluralRule(PluralRule::fromString('nplurals=3; plural=n'));
        $this->assertEquals(2, $domain->getPluralRule()->evaluate(2));
    }

    public function testPluralRuleDefault()
    {
        $domain = new TextDomain();
        $this->assertEquals(1, $domain->getPluralRule()->evaluate(0));
        $this->assertEquals(0, $domain->getPluralRule()->evaluate(1));
        $this->assertEquals(1, $domain->getPluralRule()->evaluate(2));
    }

    public function testMerging()
    {
        $domainA = new TextDomain(['foo' => 'bar', 'bar' => 'baz']);
        $domainB = new TextDomain(['baz' => 'bat', 'bar' => 'bat']);
        $domainA->merge($domainB);

        $this->assertEquals('bar', $domainA['foo']);
        $this->assertEquals('bat', $domainA['bar']);
        $this->assertEquals('bat', $domainA['baz']);
    }

    public function testMergingIncompatibleTextDomains()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not compatible');

        $domainA = new TextDomain();
        $domainB = new TextDomain();
        $domainA->setPluralRule(PluralRule::fromString('nplurals=3; plural=n'));
        $domainB->setPluralRule(PluralRule::fromString('nplurals=2; plural=n'));

        $domainA->merge($domainB);
    }

    public function testMergingTextDomainsWithPluralRules()
    {
        $domainA = new TextDomain();
        $domainB = new TextDomain();

        $domainA->merge($domainB);
        $this->assertFalse($domainA->hasPluralRule());
        $this->assertFalse($domainB->hasPluralRule());
    }

    public function testMergingTextDomainWithPluralRuleIntoTextDomainWithoutPluralRule()
    {
        $domainA = new TextDomain();
        $domainB = new TextDomain();
        $domainB->setPluralRule(PluralRule::fromString('nplurals=3; plural=n'));

        $domainA->merge($domainB);
        $this->assertEquals(3, $domainA->getPluralRule()->getNumPlurals());
        $this->assertEquals(3, $domainB->getPluralRule()->getNumPlurals());
    }

    public function testMergingTextDomainWithoutPluralRuleIntoTextDomainWithPluralRule()
    {
        $domainA = new TextDomain();
        $domainB = new TextDomain();
        $domainA->setPluralRule(PluralRule::fromString('nplurals=3; plural=n'));

        $domainA->merge($domainB);
        $this->assertEquals(3, $domainA->getPluralRule()->getNumPlurals());
        $this->assertFalse($domainB->hasPluralRule());
    }

    public function testSerialize()
    {
        $plA = PluralRule::fromString('nplurals=3; plural=n');
        $plB = PluralRule::fromString('nplurals=4; plural=n');

        $domainA = new TextDomain(['foo' => 'bar', 'bar' => 'baz']);
        $domainA->setPluralRule($plA);

        $domainB = new TextDomain(['baz' => 'bat', 'bar' => 'bat']);
        $domainB->setPluralRule($plB);

        $serA = serialize($domainA);
        $serB = serialize($domainB);

        $unserA = unserialize($serA);
        $unserB = unserialize($serB);

        $this->assertEquals($domainA, $unserA);
        $this->assertEquals($plA, $unserA->getPluralRule());
        $this->assertEquals($domainB, $unserB);
        $this->assertEquals($plB, $unserB->getPluralRule());
    }
}
