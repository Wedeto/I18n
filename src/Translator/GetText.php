<?php
/**
 * This is part of WASP, the Web Application Software Platform.
 * This class is adapted from Zend/I18n/Translator/Loaders/Gettext.
 * The Zend framework is published on the New BSD license, and as such,
 * this class is also covered by the New BSD license as a derivative work.
 * The original copright notice is maintained below.
 */

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace WASP\I18n\Translator;

use WASP\I18n\Translator\Plural\Rule as PluralRule;

/**
 * GetText loader.
 */
class GetText
{
    /** Current file pointer. */
    protected $file;

    /** Whether the current file is little endian. */
    protected $littleEndian;

    /**
     * load(): defined by FileLoaderInterface.
     *
     * @param  string $locale
     * @param  string $filename
     * @return TextDomain
     * @throws InvalidArgumentException
     */
    public function load(string $locale, string $filename)
    {
        if (!is_file($filename) || !is_readable($filename))
            throw new \InvalidArgumentException(sprintf('Could not find or open file %s for reading', $filename));

        $textDomain = new TextDomain;
        $this->file = fopen($filename, 'rb');

        // Verify magic number
        $magic = fread($this->file, 4);

        if ($magic == "\x95\x04\x12\xde")
        {
            $this->littleEndian = false;
        }
        elseif ($magic == "\xde\x12\x04\x95")
        {
            $this->littleEndian = true;
        }
        else
        {
            fclose($this->file);
            throw new \InvalidArgumentException($filename . ' is not a valid gettext file');
        }

        // Verify major revision (only 0 and 1 supported)
        $majorRevision = ($this->readInteger() >> 16);

        if ($majorRevision !== 0 && $majorRevision !== 1)
        {
            fclose($this->file);
            throw new \InvalidArgumentException($filename . ' has an unknown major revision');
        }

        // Gather main information
        $numStrings = $this->readInteger();
        $originalStringTableOffset = $this->readInteger();
        $translationStringTableOffset = $this->readInteger();

        // Usually there follow size and offset of the hash table, but we have
        // no need for it, so we skip them.
        fseek($this->file, $originalStringTableOffset);
        $originalStringTable = $this->readIntegerList(2 * $numStrings);

        fseek($this->file, $translationStringTableOffset);
        $translationStringTable = $this->readIntegerList(2 * $numStrings);

        // Read in all translations
        for ($current = 0; $current < $numStrings; ++$current)
        {
            $sizeKey = $current * 2 + 1;
            $offsetKey = $current * 2 + 2;
            $originalStringSize = $originalStringTable[$sizeKey];
            $originalStringOffset = $originalStringTable[$offsetKey];
            $translationStringSize = $translationStringTable[$sizeKey];
            $translationStringOffset = $translationStringTable[$offsetKey];

            $originalString = [''];
            if ($originalStringSize > 0) 
            {
                fseek($this->file, $originalStringOffset);
                $originalString = explode("\0", fread($this->file, $originalStringSize));
            }

            if ($translationStringSize > 0)
            {
                fseek($this->file, $translationStringOffset);
                $translationString = explode("\0", fread($this->file, $translationStringSize));

                if (count($originalString) > 1 && count($translationString) > 1)
                {
                    $textDomain[$originalString[0]] = $translationString;

                    array_shift($originalString);

                    foreach ($originalString as $string)
                        if (!isset($textDomain[$string]))
                            $textDomain[$string] = '';
                }
                else
                    $textDomain[$originalString[0]] = $translationString[0];
            }
        }

        // Read header entries
        if (array_key_exists('', $textDomain))
        {
            $rawHeaders = explode("\n", trim($textDomain['']));

            foreach ($rawHeaders as $rawHeader)
            {
                list($header, $content) = explode(':', $rawHeader, 2);

                if (trim(strtolower($header)) === 'plural-forms')
                    $textDomain->setPluralRule(PluralRule::fromString($content));
            }

            unset($textDomain['']);
        }

        fclose($this->file);

        return $textDomain;
    }

    /**
     * Read a single integer from the current file.
     *
     * @return int
     */
    protected function readInteger()
    {
        if ($this->littleEndian)
            $result = unpack('Vint', fread($this->file, 4));
        else
            $result = unpack('Nint', fread($this->file, 4));

        return $result['int'];
    }

    /**
     * Read an integer from the current file.
     *
     * @param  int $num
     * @return int
     */
    protected function readIntegerList(int $num)
    {
        if ($this->littleEndian)
            return unpack('V' . $num, fread($this->file, 4 * $num));

        return unpack('N' . $num, fread($this->file, 4 * $num));
    }

    /**
     * Indicate whether or not to use the include_path to resolve translation files
     *
     * @param bool $flag
     * @return self
     */
    public function setUseIncludePath(bool $flag = true)
    {
        $this->useIncludePath = $flag;
        return $this;
    }

    /**
     * Are we using the include_path to resolve translation files?
     *
     * @return bool
     */
    public function useIncludePath()
    {
        return $this->useIncludePath;
    }
}
