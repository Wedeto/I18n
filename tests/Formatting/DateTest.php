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

class DateTest extends TestCase
{
    public function getDataSet()
    {
        return [
            'nl' => [
                '2017-05-12 22:15:00' => [
                    'vrijdag 12 mei 2017 - 22:15',
                    'vr 12 mei 2017 22:15',
                    '12/5/17 10:15 p.m.'
                ],
                '2000-01-01 00:00:00' => [
                    'zaterdag 1 januari 2000 - 00:00',
                    'za 1 jan. 2000 0:0',
                    '1/1/00 0:00 a.m.'
                ]
            ],
            'en' => [
                '2017-05-12 22:15:00' => [
                    'Friday 12 May 2017 - 22:15',
                    'Fri 12 May 2017 22:15',
                    '12/5/17 10:15 PM'
                ],
                '2000-01-01 00:00:00' => [
                    'Saturday 1 January 2000 - 00:00',
                    'Sat 1 Jan 2000 0:0',
                    '1/1/00 0:00 AM'
                ]
            ]
        ];
    }

    public function testFormatting()
    {
        $formats = [
            'eeee d MMMM yyyy - HH:mm',
            'eee d MMM yyyy H:m',
            'd/M/yy K:mm a'
        ];

        $dtz = new \DateTimeZone("UTC");
        $data = $this->getDataSet();
        foreach ($data as $locale => $times)
        {
            $l = new Locale($locale);
            $df = new Date($l);
            $df->setTimeZone($dtz);

            foreach ($times as $stamp => $representations)
            {
                $epoch = strtotime($stamp . ' UTC');
                $dt = new \DateTime('@' . $epoch, $dtz);

                for ($i = 0; $i < count($formats); ++$i)
                {
                    $df->setDateFormat($formats[$i], Date::DATETIME);
                    $str = $df->format($dt, Date::DATETIME);
                    $this->assertEquals($representations[$i], $str, "Asserting that $stamp when formatted in representation $i yields {$representations[$i]}");

                    $parsed = $df->parse($representations[$i], Date::DATETIME);
                    $parsed_stamp = $parsed->getTimestamp();
                    $this->assertEquals($epoch, $parsed_stamp, "Asserting that both $stamp and {$representations[$i]} convert to the same stamp");
                }
            }
        }
    }
}

