<?php
namespace AnalyticsConverter;

use Doctrine\Common\Lexer\AbstractLexer;

class Lexer extends AbstractLexer {

    public const T_NONE              = 1;

    public const T_COMMA = 2;
    public const T_DOUBLEQUOTE = 3;
    public const T_DOUBLEPOINT = 4;
    public const T_NUMBER = 5;
    public const T_CLOSE_PARENTHESIS = 6;
    public const T_OPEN_PARENTHESIS  = 7;
    public const T_STRING = 8;
    public const T_BOOLEAN_FALSE = 9;
    public const T_BOOLEAN_TRUE = 10;

    public const T_OPEN_CURLY_BRACE  = 18;
    public const T_CLOSE_CURLY_BRACE = 19;
    public const T_OPEN_BRACKET  = 20;
    public const T_CLOSE_BRACKET = 21;

    public const T_COMMENT_PARAMSTART = 100;
    public const T_COMMENT_PARAMEND = 101;
    public const T_COMMENT_NOUSE = 102;

    public const T_DATE_EMPTY = 110;
    public const T_DATE_WITH_PARAM = 111;

    private $commentRegexes = [
        //'\\/\* param\:([a-z0-9]*) \*\\/' => self::T_COMMENT_PARAMSTART,
        '\\/\* param\:([a-z0-9]*) \*\\/' => self::T_COMMENT_PARAMSTART,
        '\\/\* endparam \*\\/' => self::T_COMMENT_PARAMEND,
        '\\/\*(.*)\*\\/' => self::T_COMMENT_NOUSE,
        'new Date\(\)' => self::T_DATE_EMPTY,
        'new Date\((.*)\)' => self::T_DATE_WITH_PARAM
    ];

    /**
     * Creates a new query scanner object.
     *
     * @param string $input A query string.
     */
    public function __construct($input)
    {
        $this->setInput($input);
    }

    protected function getModifiers()
    {
        return 'im';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCatchablePatterns()
    {
        return array_merge(
            [
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?', // numbers
            "\"(?:[^\"]|'')*\"", // quoted strings
            'true',
            'false'
            ],
            array_keys($this->commentRegexes)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getNonCatchablePatterns()
    {
        return ['\s+', '(.)'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(&$value)
    {
        $type = self::T_NONE;

        switch (true) {
            case (is_numeric($value)):
                return self::T_NUMBER;
            case ($value[0] === "\""):
                $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));
                return self::T_STRING;
            case ($value === '('):
                return self::T_OPEN_PARENTHESIS;
            case ($value === ')'):
                return self::T_CLOSE_PARENTHESIS;
            case ($value === '{'):
                return self::T_OPEN_CURLY_BRACE;
            case ($value === '}'):
                return self::T_CLOSE_CURLY_BRACE;
            case ($value === '['):
                return self::T_OPEN_BRACKET;
            case ($value === ']'):
                return self::T_CLOSE_BRACKET;
            case ($value === ','):
                return self::T_COMMA;
            case ($value === '"'):
                return self::T_DOUBLEQUOTE;
            case ($value === ':'):
                return self::T_DOUBLEPOINT;
            case ($value === 'true'):
                return self::T_BOOLEAN_TRUE;
            case ($value === 'false'):
                return self::T_BOOLEAN_FALSE;

            // Default
            default:
                foreach ($this->commentRegexes as $regex => $regexType) {
                    if (preg_match('/'.$regex.'/i', $value)) {
                        return $regexType;
                    }
                }
        }

        return $type;
    }

}
