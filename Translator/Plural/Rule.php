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
 * Plural rule evaluator.
 */
class Rule implements \Serializable
{
    /**
     * Parser instance.
     */
    protected static $parser;

    /**
     * Abstract syntax tree.
     */
    protected $ast;

    /**
     * Number of plurals in this rule.
     */
    protected $numPlurals;

    /**
     * Create a new plural rule.
     *
     * @param int $numPlurals
     * @param array $ast
     * @return WASP\I18n\Translator\Plural\Rule
     */
    protected function __construct(int $numPlurals, array $ast)
    {
        $this->numPlurals = $numPlurals;
        $this->ast = $ast;
    }

    /**
     * Evaluate a number and return the plural index.
     *
     * @param int $number
     * @return int
     * @throws OutOfRangeException
     */
    public function evaluate(int $number)
    {
        $result = $this->evaluateAstPart($this->ast, abs((int) $number));

        if ($result < 0 || $result >= $this->numPlurals)
        {
            throw new \OutOfRangeException(
                sprintf('Calculated result %s is between 0 and %d', $result, ($this->numPlurals - 1))
            );
        }

        return $result;
    }

    public function serialize()
    {
        $data = array(
            'numPlurals' => $this->numPlurals,
            'ast' => $this->ast
        );
        return serialize($data);
    }

    public function unserialize(string $data)
    {
        $data = unserialize($data);
        $this->numPlurals = $data['numPlurals'];
        $this->ast = $data['ast'];
    }

    /**
     * Get number of possible plural forms.
     *
     * @return int
     */
    public function getNumPlurals()
    {
        return $this->numPlurals;
    }

    /**
     * Evaluate a part of an ast.
     *
     * @param  array $ast
     * @param  int $number
     * @return int
     * @throws LogicException
     */
    protected function evaluateAstPart(array $ast, int $number)
    {
        switch ($ast['id'])
        {
            case 'number':
                return $ast['arguments'][0];

            case 'n':
                return $number;

            case '+':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       + $this->evaluateAstPart($ast['arguments'][1], $number);

            case '-':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       - $this->evaluateAstPart($ast['arguments'][1], $number);

            case '/':
                // Integer division
                return floor(
                    $this->evaluateAstPart($ast['arguments'][0], $number)
                    / $this->evaluateAstPart($ast['arguments'][1], $number)
                );

            case '*':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       * $this->evaluateAstPart($ast['arguments'][1], $number);

            case '%':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       % $this->evaluateAstPart($ast['arguments'][1], $number);

            case '>':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       > $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '>=':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       >= $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '<':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       < $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '<=':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       <= $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '==':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       == $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '!=':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       != $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '&&':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       && $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '||':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       || $this->evaluateAstPart($ast['arguments'][1], $number)
                       ? 1 : 0;

            case '!':
                return !$this->evaluateAstPart($ast['arguments'][0], $number)
                       ? 1 : 0;

            case '?':
                return $this->evaluateAstPart($ast['arguments'][0], $number)
                       ? $this->evaluateAstPart($ast['arguments'][1], $number)
                       : $this->evaluateAstPart($ast['arguments'][2], $number);

            default:
                throw new \LogicException(sprintf('Unknown token: %s', $ast['id']));
        }
    }

    /**
     * Create a new rule from a string.
     *
     * @param string $string
     * @throws LogicException
     * @return WASP\I18n\Translator\Plural\Rule
     */
    public static function fromString(string $string)
    {
        if (static::$parser === null)
            static::$parser = new Parser();

        if (!preg_match('(nplurals=(?P<nplurals>\d+))', $string, $match))
            throw new \LogicException(sprintf('Unknown or invalid parser rule: %s', $string));

        $numPlurals = (int)$match['nplurals'];

        if (!preg_match('(plural=(?P<plural>[^;\n]+))', $string, $match))
            throw new \LogicException(sprintf('Unknown or invalid parser rule: %s', $string));

        $tree = static::$parser->parse($match['plural']);
        $ast  = static::createAst($tree);

        return new static($numPlurals, $ast);
    }

    /**
     * Create an AST from a tree.
     *
     * Theoretically we could just use the given Symbol, but that one is not
     * so easy to serialize and also takes up more memory.
     *
     * @param WASP\I18n\Translator\Plural\Symbol $symbol
     * @return array
     */
    protected static function createAst(Symbol $symbol)
    {
        $ast = array('id' => $symbol->id, 'arguments' => []);

        switch ($symbol->id)
        {
            case 'n':
                break;

            case 'number':
                $ast['arguments'][] = $symbol->value;
                break;

            case '!':
                $ast['arguments'][] = static::createAst($symbol->first);
                break;

            case '?':
                $ast['arguments'][] = static::createAst($symbol->first);
                $ast['arguments'][] = static::createAst($symbol->second);
                $ast['arguments'][] = static::createAst($symbol->third);
                break;

            default:
                $ast['arguments'][] = static::createAst($symbol->first);
                $ast['arguments'][] = static::createAst($symbol->second);
                break;
        }

        return $ast;
    }
}
