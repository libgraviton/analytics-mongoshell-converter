<?php
/**
 * generates a class based on the input
 */
namespace AnalyticsConverter;

use Nette\PhpGenerator\PhpFile;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ClassGenerator {

	private $name;
	private $pipeline;
	private $conditionals = [];
	private $conditionalParamMap = [];
	private $classNamespace = 'Foo';

	public function __construct($name, $pipeline, $conditionals = [], $conditionalParamMap = []) {
		$this->name = $this->generateName($name);
		$this->pipeline = $pipeline;
		$this->conditionals = $conditionals;
		$this->conditionalParamMap = $conditionalParamMap;
	}

	/**
	 * set ClassNamespace
	 *
	 * @param string $classNamespace classNamespace
	 *
	 * @return void
	 */
	public function setClassNamespace($classNamespace) {
		$this->classNamespace = $classNamespace;
	}

	public function generate() {

		$file = new PhpFile();
		$file->addComment('This file is auto-generated.');
		$file->setStrictTypes();

		$namespace = $file->addNamespace($this->classNamespace);
		$class = $namespace->addClass($this->name);
		$class
			->setFinal()
			->setExtends('\Graviton\AnalyticsBase\Pipeline\PipelineAbstract')
			->addComment("Generated Graviton Pipeline class");

		$class->addMethod('getPipeline')
			->setReturnType('array')
			->setVisibility('protected')
			->setBody('return '.$this->pipeline .';');

		$class = $this->addConditionals($class);

		return (string) $file;
	}

	public function getName()
	{
		return $this->name;
	}

	private function generateName($name) {
		$name = str_replace(".js", "", $name);
		$name = str_replace(".", " ", $name);
		$name = ucwords($name);
		$name = str_replace(" ", "", $name);
		return $name;
	}

	private function addConditionals($class) {
		if (empty($this->conditionals)) {
			return $class;
		}

		foreach ($this->conditionals as $name => $conditional) {
			// remove trailing comma if there
			$conditional = trim(implode(' ', $conditional));
			if (substr($conditional, -1) == ',') {
				$conditional = substr($conditional, 0, -1);
			}

			$body = '
				if (!$this->hasParam("'.$this->conditionalParamMap[$name].'")) {
					return self::EMPTY_STRING;
				}
			'.PHP_EOL.'return '.$conditional.';';

			$class->addMethod('getConditional'.$name)
				->setBody($body);
		}

		return $class;
	}

}
