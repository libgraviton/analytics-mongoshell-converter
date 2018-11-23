<?php
/**
 * a Parser that outputs PHP based on our Lexer input
 */
namespace AnalyticsConverter;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Parser
{
    private $lexer;

    private $lines = [];
    private $currentLevel = 1;
    private $mongoDateClass = '\MongoDB\BSON\UTCDateTime';

    private $conditionals = [];
    private $conditionalParamMap = [];
    private $currentConditional;

    public function __construct($input)
    {
        $this->lexer = new Lexer($input);
    }

	/**
	 * set MongoDateClass
	 *
	 * @param string $mongoDateClass mongoDateClass
	 *
	 * @return void
	 */
	public function setMongoDateClass($mongoDateClass) {
		$this->mongoDateClass = $mongoDateClass;
	}

	/**
	 * parses our stuff
	 *
	 * @return string parsed pipeline as a script
	 */
    public function parse()
    {
        $this->lexer->moveNext();

        while (true) {
            if (!$this->lexer->lookahead) {
                break;
            }

            $this->lexer->moveNext();
            $thisLine = null;

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
					$thisLine = '$this->getParam("'.$paramName.'")';
                    // scroll to end of param
                    while ($this->lexer->token['type'] != Lexer::T_COMMENT_PARAMEND) {
                        $this->lexer->moveNext();
                    }
                    break;
				case Lexer::T_COMMENT_IFPARAMSTART:
					$conditionalId = uniqid();
					$this->addContent('$this->getConditional'.$conditionalId.'(),');
					$this->openConditional($conditionalId, $this->lexer->lookahead['value']);
					break;
				case Lexer::T_COMMENT_IFPARAMEND:
					$this->closeConditional();
					break;
                case Lexer::T_DATE_EMPTY:
                    $thisLine = 'new '.$this->mongoDateClass.'()';
                    break;
                case Lexer::T_DATE_WITH_PARAM:
					$paramName = $this->lexer->lookahead['value'];
					$thisLine = '$this->getParam("'.$paramName.'")';
                    break;
                case Lexer::T_BOOLEAN_TRUE:
                    $thisLine = 'true';
                    break;
                case Lexer::T_BOOLEAN_FALSE:
                    $thisLine = 'false';
                    break;
				case Lexer::T_COMMA:
				case Lexer::T_NUMBER:
				case Lexer::T_DASH:
					$thisLine = $this->lexer->token['value'];
					break;
                default:
                    // nothing
            }

            if (!is_null($thisLine)) {
				$this->addContent($thisLine);
			}
        }

        return implode(PHP_EOL, $this->lines);
    }

	/**
	 * get Conditionals
	 *
	 * @return array Conditionals
	 */
	public function getConditionals() {
		return $this->conditionals;
	}

	/**
	 * get ConditionalParamMap
	 *
	 * @return array ConditionalParamMap
	 */
	public function getConditionalParamMap() {
		return $this->conditionalParamMap;
	}

    private function getLevelIndent()
    {
        return str_repeat(' ', $this->currentLevel * 2);
    }

    private function addContent($content)
	{
		if (is_null($this->currentConditional)) {
			$this->lines[] = $this->getLevelIndent() . $content;
		} else {
			$this->addToConditional($this->currentConditional, $content);
		}
	}

    private function openConditional($name, $paramName)
	{
		$this->currentConditional = $name;
		$this->conditionalParamMap[$name] = $paramName;
		return $this->currentConditional;
	}

	private function closeConditional()
	{
		$this->currentConditional = null;
	}

	private function addToConditional($conditional, $content)
	{
		$this->conditionals[$conditional][] = $content;
	}
}
