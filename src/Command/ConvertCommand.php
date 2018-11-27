<?php
/**
 * convert command
 */
namespace AnalyticsConverter\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

/**
 * @author   List of contributors <https://github.com/libgraviton/compose-transpiler/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ConvertCommand extends Command
{

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('convert')
            ->setDescription('Convert analytics')
            ->addArgument(
                'scanDir',
                InputArgument::REQUIRED,
                'Which directory to scan'
            )
            ->addArgument(
                'outDir',
                InputArgument::REQUIRED,
                'Where to put generated classes'
            )
			->addOption(
				'mongoDateClass',
				'm',
				InputOption::VALUE_OPTIONAL,
				'Override which Mongo Date class is used'
			)
			->addOption(
				'classNamespace',
				'c',
				InputOption::VALUE_OPTIONAL,
				'Override the generated class namespace'
			);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  User input on console
     * @param OutputInterface $output Output of the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // find php
        $phpFinder = new PhpExecutableFinder();
        $phpProcess = $phpFinder->find();

		$fs = new Filesystem();
		$finder = new Finder();
		$finder
			->files()
			->in($input->getArgument('scanDir'))
			->name('*.js')
			->notName('_*')
			->sortByName();

		foreach ($finder as $file) {
			$parser = new \AnalyticsConverter\Parser($file->getContents());
			if (!is_null($input->getOption('mongoDateClass'))) {
				$parser->setMongoDateClass($input->getOption('mongoDateClass'));
			}

			$classGenerator = new \AnalyticsConverter\ClassGenerator(
				$file->getFilename(),
				$parser->parse(),
				$parser->getConditionals(),
				$parser->getConditionalParamMap()
			);

			if (!is_null($input->getOption('classNamespace'))) {
				$classGenerator->setClassNamespace($input->getOption('classNamespace'));
			}

			$targetFileName = $input->getArgument('outDir').'/'.$classGenerator->getName().'.php';

			$fs->dumpFile($targetFileName, $classGenerator->generate());
			$output->writeln("wrote php file '".$targetFileName."'");

			// php lint the file..
            $proc = new Process([$phpProcess, '-l', $targetFileName]);
            $proc->start();
            $proc->wait();

            if ($proc->getExitCode() !== 0) {
                throw new \LogicException(
                    "Invalid PHP code generated in file '".$targetFileName."' - check your pipeline!"
                );
            } else {
                $output->writeln("Checked PHP syntax - all OK!");
            }
		}
    }
}
