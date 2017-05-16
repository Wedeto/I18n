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

namespace Wedeto\I18n\Translator;

use Psr\Log\LogLevel;
use Wedeto\Util\Hook;
use Wedeto\Log\Writer\AbstractWriter;
use Wedeto\Log\Logger;

/**
 * TranslationLogger hooks into the logger of Wedeto.I18n.Translator.Translator and
 * writes untranslated strings to a POT-like file, including the line where the
 * translated string was requested.
 * 
 * Take note: the file is not aggregated and duplicates will occur.
 */
class TranslationLogger extends AbstractWriter 
{
    /** The pattern of the POT file */
    private $pattern;

    /**
     * Construct the writer with a pattern. The pattern should contain
     * two placeholders used by printf, the first should be the text domain
     * and the second the locale.
     * @param strint $pattern The pattern to use to write the files
     */
    public function __construct($pattern)
    {
        $this->pattern = $pattern;
        $this->min_level = Logger::getLevelNumeric(LogLevel::DEBUG);
    }

    /**
     * Write a untranslated string to the output file
     *
     * $param string $level The log level
     * @param string $message The log message. Only messages starting with 'Untranslated' are accepted
     * @param array $context Should contain information about the untranslated string:
     *                       msgid, msgid_plural, locale and domain.
     */
    public function write(string $level, string $message, array $context)
    {
        if (substr($message, 0, 12) !== "Untranslated")
            return;

        if (!isset($context['locale']))
            return;

        if (!isset($context['domain']))
            return;

        if (!isset($context['msgid']))
            return;

        // Detect source
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        $ignore = array(
            'Wedeto\I18n\I18n',
            'Wedeto\I18n\Translator\Translator',
            'Psr\Log\AbstractLogger',
            'Wedeto\Log\Logger',
            'Wedeto\I18n\Translator\TranslationLogger'
        );

        $informative_trace = null;
        foreach ($bt as $trace)
        {
            if (isset($trace['class']))
            {
                $class = $trace['class'];
                if (in_array($class, $ignore, true))
                    continue;
            }
            $informative_trace = $trace;
            break;
        }
        
        $locale = $context['locale'];
        $domain = $context['domain'];
        $msgid = $context['msgid'];
        $msgplural = isset($context['msgid_plural']) ? $context['msgid_plural'] : null;

        $file = sprintf($this->pattern, $domain, $locale);

        if (!file_exists($file))
        {
            touch($file);
            Hook::execute("Wedeto.IO.FileCreated", ['filename' => $file]);
        }

        $fh = fopen($file, "a");
        fprintf($fh, "#. Untranslated string at %s\n", date("Y-m-d H:i:s"));

        // @codeCoverageIgnoreStart
        // PHPUnit strips file and line information from the trace
        if (isset($informative_trace['file']) && isset($informative_trace['line']))
            fprintf($fh, "#: %s:%d\n", $informative_trace['file'], $informative_trace['line']);
        // @codeCoverageIgnoreEnd

        $msgid = str_replace('"', '\\"', $msgid);

        if ($msgplural)
            $msgplural = str_replace('"', '\\"', $msgid);

        fprintf($fh, "msgid \"%s\"\n", $msgid);

        if ($msgplural)
        {
            fprintf($fh, "msgid_plural \"%s\"\n", $msgplural);
            fprintf($fh, "msgstr[0] \"\"\n");
            fprintf($fh, "msgstr[1] \"\"\n");
        }
        else
        {
            fprintf($fh, "msgstr \"\"\n");
        }

        fprintf($fh, "\n");
        fclose($fh);
    }
}
