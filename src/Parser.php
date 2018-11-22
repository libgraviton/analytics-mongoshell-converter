<?php
namespace AnalyticsConverter;

class Parser
{
    private $lexer;

    private $lines = [];

    private $currentLevel = 1;

    public function __construct($input)
    {
        $this->lexer = new Lexer($input);
    }



    public function parse()
    {
        //$this->lexer->setInput($string);
        $this->lexer->moveNext();

        while (true) {
            if (!$this->lexer->lookahead) {
                break;
            }

            $this->lexer->moveNext();
            $thisLine = '';

            switch ($this->lexer->token['type']) {
                case Lexer::T_OPEN_PARENTHESIS:
                case Lexer::T_OPEN_BRACKET:
                case Lexer::T_OPEN_CURLY_BRACE:
                    $thisLine = '[';
                    $this->currentLevel++;
                    break;
                case Lexer::T_CLOSE_PARENTHESIS:
                case Lexer::T_CLOSE_BRACKET:
                case Lexer::T_CLOSE_CURLY_BRACE:
                    $thisLine = ']';
                    $this->currentLevel--;
                    break;
                case Lexer::T_STRING:
                    $thisLine = "'".str_replace("'", "\'", $this->lexer->token['value'])."'";
                    break;
                case Lexer::T_DOUBLEPOINT:
                    $thisLine = '=>';
                    break;
                case Lexer::T_COMMENT_PARAMSTART:
                    $paramName = $this->lexer->lookahead['value'];
                    $thisLine = '/* START PARAM '.$paramName. '*/ $param ';
                    // scroll to end of param
                    while ($this->lexer->token['type'] != Lexer::T_COMMENT_PARAMEND) {
                        $this->lexer->moveNext();
                    }
                    $thisLine .= ' /* PARAM END */ ';

                    break;
                case Lexer::T_DATE_EMPTY:
                    $thisLine = 'new \MongoDate()';
                    break;
                case Lexer::T_DATE_WITH_PARAM:
                    $theParam = $this->lexer->lookahead['value'];
                    $thisLine = 'new \MongoDate()';
                    break;
                case Lexer::T_COMMENT_NOUSE:
                    // skip
                    $this->lexer->moveNext();
                    // IGNORE THIS
                    break;
                case Lexer::T_BOOLEAN_TRUE:
                    $thisLine = 'true';
                    break;
                case Lexer::T_BOOLEAN_FALSE:
                    $thisLine = 'false';
                    break;
                default:
                    $thisLine = $this->lexer->token['value'];
                    // nope
            }

            $this->lines[] = $this->getLevelIndent() . $thisLine;

            //echo $this->lexer->token['value'].PHP_EOL;
        }

        return implode(PHP_EOL, $this->lines);
    }

    private function getLevelIndent()
    {
        return str_repeat(' ', $this->currentLevel * 2);
    }


    // ...
}
