<?php
/**
 * This is part of WASP, the Web Application Software Platform.
 * This class is adapted from Zend/I18n/Translator/Plural/Rule.
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

namespace WASP\I18n\Translator\Plural;

/**
 * Parser symbol.
 *
 * All properties in the symbol are defined as public for easier and faster
 * access from the applied closures. An exception are the closure properties
 * themselves, as they have to be accessed via the appropriate getter and
 * setter methods.
 */
class Symbol
{
    /**
     * Parser instance.
     */
    public $parser;

    /**
     * Node or token type name.
     */
    public $id;

    /**
     * Left binding power (precedence).
     */
    public $leftBindingPower;

    /**
     * Getter for null denotation.
     */
    protected $nullDenotationGetter;

    /**
     * Getter for left denotation.
     */
    protected $leftDenotationGetter;

    /**
     * Value used by literals.
     */
    public $value;

    /**
     * First node value.
     */
    public $first;

    /**
     * Second node value.
     */
    public $second;

    /**
     * Third node value.
     */
    public $third;

    /**
     * Create a new symbol.
     *
     * @param Parser $parser
     * @param string $id
     * @param int $leftBindingPower
     */
    public function __construct(Parser $parser, string $id, int $leftBindingPower)
    {
        $this->parser = $parser;
        $this->id = $id;
        $this->leftBindingPower = $leftBindingPower;
    }

    /**
     * Set the null denotation getter.
     *
     * @param callable $getter
     * @return Symbol
     */
    public function setNullDenotationGetter(callable $getter)
    {
        $this->nullDenotationGetter = $getter;
        return $this;
    }

    /**
     * Set the left denotation getter.
     *
     * @param callable $getter
     * @return Symbol
     */
    public function setLeftDenotationGetter(callable $getter)
    {
        $this->leftDenotationGetter = $getter;
        return $this;
    }

    /**
     * Get null denotation.
     *
     * @throws LogicException
     * @return Symbol
     */
    public function getNullDenotation()
    {
        if ($this->nullDenotationGetter === null)
            throw new \LogicException(sprintf('Syntax error: %s', $this->id));

        $function = $this->nullDenotationGetter;
        return $function($this);
    }

    /**
     * Get left denotation.
     *
     * @param  Symbol $left
     * @throws LogicException
     * @return Symbol
     */
    public function getLeftDenotation($left)
    {
        if ($this->leftDenotationGetter === null)
            throw new LogicException(sprintf('Unknown operator: %s', $this->id));

        $function = $this->leftDenotationGetter;
        return $function($this, $left);
    }
}
