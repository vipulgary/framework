<?php
/**
 * Thoughtyards Base Class.
 *
 * This script is meant to be run on command line to execute
 * one of the pre-defined console commands.
 *
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo
 * @license GNU
 */
namespace Thoughtyards\Kinetics\ConsoleKernel;
use Thoughtyards\Kinetics\Components\CApplication;

class TYConsoleApp extends CApplication
{
	/**
	 * @var array mapping from command name to command configurations.
	 * Each command configuration can be either a string or an array.
	 * If the former, the string should be the file path of the command class.
	 * If the latter, the array must contain a 'class' element which specifies
	 * The rest name-value pairs in the array are used to initialize
	 * the corresponding command properties. For example,
	 * <pre>
	 * array(
	 *   'email'=>array(
	 *      'class'=>'path.to.Mailer',
	 *      'interval'=>3600,
	 *   ),
	 *   'log'=>'path/to/LoggerCommand.php',
	 * )
	 * </pre>
	 */
	public $commandMap=array();

	private $_commandPath;
	private $_runner;

	/**
	 * Initializes the application by creating the command runner.
	 */
	protected function init()
	{
		parent::init();
		if(!isset($_SERVER['argv'])) // || strncasecmp(php_sapi_name(),'cli',3))
			die('This script must be run from the command line.');
		$this->_runner=$this->createCommandRunner();
		$this->_runner->commands=$this->commandMap;
		$this->_runner->addCommands($this->getCommandPath());
	}

	/**
	 * Processes the user request.
	 * This method uses a console command runner to handle the particular user command.
	 * Since version 1.1.11 this method will exit application with an exit code if one is returned by the user command.
	 */
	public function processRequest()
	{
		$exitCode=$this->_runner->run($_SERVER['argv']);
		if(is_int($exitCode))
			$this->end($exitCode);
	}

	/**
	 * Creates the command runner instance.
	 * @return CConsoleCommandRunner the command runner
	 */
	protected function createCommandRunner()
	{
		return new CConsoleCommandRunner;
	}

	/**
	 * Displays the captured PHP error.
	 * This method displays the error in console mode when there is
	 * no active error handler.
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $file error file
	 * @param string $line error line
	 */
	public function displayError($code,$message,$file,$line)
	{
		echo "PHP Error[$code]: $message\n";
		echo "    in file $file at line $line\n";
		$trace=debug_backtrace();
		// skip the first 4 stacks as they do not tell the error position
		if(count($trace)>4)
			$trace=array_slice($trace,4);
		foreach($trace as $i=>$t)
		{
			if(!isset($t['file']))
				$t['file']='unknown';
			if(!isset($t['line']))
				$t['line']=0;
			if(!isset($t['function']))
				$t['function']='unknown';
			echo "#$i {$t['file']}({$t['line']}): ";
			if(isset($t['object']) && is_object($t['object']))
				echo get_class($t['object']).'->';
			echo "{$t['function']}()\n";
		}
	}

	/**
	 * Displays the uncaught PHP exception.
	 * This method displays the exception in console mode when there is
	 * no active error handler.
	 * @param Exception $exception the uncaught exception
	 */
	public function displayException($exception)
	{
		echo $exception;
	}

	/**
	 * @return string the directory that contains the command classes. Defaults to 'protected/commands'.
	 */
	public function getCommandPath()
	{
		$applicationCommandPath = $this->getBasePath().DIRECTORY_SEPARATOR.'commands';
		if($this->_commandPath===null && file_exists($applicationCommandPath))
			$this->setCommandPath($applicationCommandPath);
		return $this->_commandPath;
	}

	/**
	 * @param string $value the directory that contains the command classes.
	 * @throws CException if the directory is invalid
	 */
	public function setCommandPath($value)
	{
		if(($this->_commandPath=realpath($value))===false || !is_dir($this->_commandPath))
			throw new ThougtException("The command path '.$value.' is not a valid directory.");
	}

	/**
	 * Returns the command runner.
	 * @return CConsoleCommandRunner the command runner.
	 */
	public function getCommandRunner()
	{
		return $this->_runner;
	}

	/**
	 * Returns the currently running command.
	 * This is shortcut method for {@link CConsoleCommandRunner::getCommand()}.
	 * @return CConsoleCommand|null the currently active command.
	 * @since 1.1.14
	 */
	public function getCommand()
	{
		return $this->getCommandRunner()->getCommand();
	}

	/**
	 * This is shortcut method for {@link CConsoleCommandRunner::setCommand()}.
	 * @param CConsoleCommand $value the currently active command.
	 * @since 1.1.14
	 */
	public function setCommand($value)
	{
		$this->getCommandRunner()->setCommand($value);
	}
}
