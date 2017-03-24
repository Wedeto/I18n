<?php
/*
This is part of WASP, the Web Application Software Platform.
It is published under the MIT Open Source License.

Copyright 2017, Egbert van der Wal

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace WASP\I18n;

use Psr\Log\LogLevel;
use WASP\Request;
use WASP\IO\File;
use WASP\Log\LogWriterInterface;
use WASP\Log\Logger;
use WASP\Platform\System;

/**
 * TranslateLogger hooks into the logger of WASP.I18n.Translator.Translator and
 * writes untranslated strings to a POT-like file, including the line where the
 * translated string was requested.
 * 
 * Take note: the file is not aggrated and duplicates may occur.
 */
class TranslateLogger implements LogWriterInterface
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
    public function write(string $level, $message, array $context)
    {
        if (!substr($message, 0, 12) == "Untranslated")
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
            'WASP\I18n\Translate',
            'WASP\I18n\Translator\Translator',
            'Psr\Log\AbstractLogger',
            'WASP\Log\Logger',
            'WASP\I18n\TranslateLogger'
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

        $req = System::request();
        $app = $req->route;

        $fh = fopen($file, "a");
        if ($app)
            fprintf($fh, "#. Untranslated string for request to %s at %s\n", $app, date("Y-m-d H:i:s"));
        else
            fprintf($fh, "#. Untranslated string at %s\n", date("Y-m-d H:i:s"));

        if ($informative_trace)
            fprintf($fh, "#: %s:%d\n", $informative_trace['file'], $informative_trace['line']);

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

        $f = new File($file);
        $f->setPermissions();
    }
}
