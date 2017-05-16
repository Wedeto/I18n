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

class LocaleTest extends TestCase
{
    public function setUp()
    {
        \Locale::setDefault('en_US');
    }

    public function testLocaleOnlyLanguage()
    {
        $l = new Locale('en');
        $dl = new Locale('nl');
        
        $this->assertEquals('en', $l->getLocale());
        $this->assertEquals('en', $l->getLanguage());
        $this->assertEmpty($l->getRegion());
        $this->assertEmpty($l->getScript());
        $this->assertEmpty($l->getVariants());
        $this->assertEmpty($l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('English', $l->getDisplayName());
        $this->assertEquals('Engels', $l->getDisplayName($dl));
        $this->assertEquals('English', $l->getDisplayLanguage());
        $this->assertEquals('Engels', $l->getDisplayLanguage($dl));

        $this->assertEmpty($l->getDisplayRegion());
        $this->assertEmpty($l->getDisplayScript());
        $this->assertEmpty($l->getDisplayVariant());

        $this->assertEqualLocaleList(['en'], $l->getFallbackList());
    }

    public function testLocaleWithRegion()
    {
        $l = new Locale('en_US');
        $dl = new Locale('nl');
        
        $this->assertEquals('en_US', $l->getLocale());
        $this->assertEquals('en', $l->getLanguage());
        $this->assertEquals('US', $l->getRegion());
        $this->assertEmpty($l->getScript());
        $this->assertEmpty($l->getVariants());
        $this->assertEmpty($l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('English (United States)', $l->getDisplayName());
        $this->assertEquals('Engels (Verenigde Staten)', $l->getDisplayName($dl));
        $this->assertEquals('English', $l->getDisplayLanguage());
        $this->assertEquals('Engels', $l->getDisplayLanguage($dl));

        $this->assertEquals('United States', $l->getDisplayRegion());
        $this->assertEmpty($l->getDisplayScript());
        $this->assertEmpty($l->getDisplayVariant());

        $this->assertEqualLocaleList(['en_US', 'en'], $l->getFallbackList());
    }

    public function testLocaleWithRegionAndVariant()
    {
        $l = new Locale('en_US_Military');
        $dl = new Locale('nl');
        
        $this->assertEquals('en_US_MILITARY', $l->getLocale());
        $this->assertEquals('en', $l->getLanguage());
        $this->assertEquals('US', $l->getRegion());
        $this->assertEmpty($l->getScript());
        $this->assertEquals(['MILITARY'], $l->getVariants());
        $this->assertEmpty($l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('English (United States, MILITARY)', $l->getDisplayName());
        $this->assertEquals('Engels (Verenigde Staten, MILITARY)', $l->getDisplayName($dl));
        $this->assertEquals('English', $l->getDisplayLanguage());
        $this->assertEquals('Engels', $l->getDisplayLanguage($dl));
        $this->assertEquals('United States', $l->getDisplayRegion());
        $this->assertEquals('MILITARY', $l->getDisplayVariant());

        $this->assertEmpty($l->getDisplayScript());

        $this->assertEqualLocaleList(['en_US_MILITARY', 'en_US', 'en'], $l->getFallbackList());
    }

    public function testLocaleWithLanguageAndScript()
    {
        $l = new Locale('zh_CHT');
        $dl = new Locale('nl');
        
        $this->assertEquals('zh_Hant', $l->getLocale());
        $this->assertEquals('zh', $l->getLanguage());
        $this->assertEmpty($l->getRegion());
        $this->assertEquals('Hant', $l->getScript());
        $this->assertEmpty($l->getVariants());
        $this->assertEmpty($l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('Chinese (Traditional)', $l->getDisplayName());
        $this->assertEquals('Chinees (traditioneel)', $l->getDisplayName($dl));
        $this->assertEquals('Chinese', $l->getDisplayLanguage());
        $this->assertEquals('Chinees', $l->getDisplayLanguage($dl));
        $this->assertEmpty($l->getDisplayRegion());
        $this->assertEmpty($l->getDisplayVariant());
        $this->assertEquals('Traditional Han', $l->getDisplayScript());

        $this->assertEqualLocaleList(['zh_Hant', 'zh'], $l->getFallbackList());
    }

    public function testLocaleWithLanguageAndScriptAndRegion()
    {
        $l = new Locale('zh_Hant_CN');
        $dl = new Locale('nl');
        
        $this->assertEquals('zh_Hant_CN', $l->getLocale());
        $this->assertEquals('zh', $l->getLanguage());
        $this->assertEquals('CN', $l->getRegion());
        $this->assertEquals('Hant', $l->getScript());
        $this->assertEmpty($l->getVariants());
        $this->assertEmpty($l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('Chinese (Traditional, China)', $l->getDisplayName());
        $this->assertEquals('Chinees (traditioneel, China)', $l->getDisplayName($dl));
        $this->assertEquals('Chinese', $l->getDisplayLanguage());
        $this->assertEquals('Chinees', $l->getDisplayLanguage($dl));
        $this->assertEquals('China', $l->getDisplayRegion());
        $this->assertEmpty($l->getDisplayVariant());
        $this->assertEquals('Traditional Han', $l->getDisplayScript());

        $this->assertEqualLocaleList(['zh_Hant_CN', 'zh_Hant', 'zh'], $l->getFallbackList());
    }

    public function testLocaleWithLanguageAndRegionAndCurrency()
    {
        $l = new Locale('en_US@currency=USD');
        $dl = new Locale('nl');
        
        $this->assertEquals('en_US@currency=USD', $l->getLocale());
        $this->assertEquals('en', $l->getLanguage());
        $this->assertEquals('US', $l->getRegion());
        $this->assertEmpty($l->getScript());
        $this->assertEmpty($l->getVariants());
        $this->assertEquals(['currency' => 'USD'], $l->getKeywords());
        $this->assertEquals($l->getLocale(), (string)$l);

        $this->assertEquals('English (United States, Currency=US Dollar)', $l->getDisplayName());
        $this->assertEquals('Engels (Verenigde Staten, valuta=Amerikaanse dollar)', $l->getDisplayName($dl));
        $this->assertEquals('English', $l->getDisplayLanguage());
        $this->assertEquals('Engels', $l->getDisplayLanguage($dl));
        $this->assertEquals('United States', $l->getDisplayRegion());
        $this->assertEmpty($l->getDisplayVariant());
        $this->assertEmpty($l->getDisplayScript());

        $this->assertEqualLocaleList(['en_US', 'en'], $l->getFallbackList());
    }

    public function testInvalidLocale()
    {
        $invalid_locales = [
            '' => 'Empty locale',
            'Engels (Verenigde Staten)' => 'Invalid locale',
            'foo # bar Boo Baz , ' => 'Invalid locale',
            'foo - bar' => 'Invalid locale',
            '!@#!@#' => 'Invalid locale',
            'this locale is a string that is too long to be a locale Therefore it should throw an error' => 'Invalid locale'
        ];

        foreach ($invalid_locales as $locale => $error)
        {
            $thrown = false;
            $lstr = $locale;
            try
            {
                $l = new Locale($locale);
                $lstr .= " -> " . $l->getLocale();
            }
            catch (I18nException $e)
            {
                $this->assertContains($error, $e->getMessage());
                $thrown = true;
            }
            $this->assertTrue($thrown, "Check that exception was thrown when parsing " . $lstr);
        }
    }

    public function assertEqualLocaleList(array $expected, array $list)
    {
        $this->assertEquals(count($expected), count($list));

        for ($i = 0; $i < count($list); ++$i)
        {
            $obj = $list[$i];
            $locale = $expected[$i];
            $this->assertEquals($list[$i]->getLocale(), $expected[$i]);
        }
    }
}

