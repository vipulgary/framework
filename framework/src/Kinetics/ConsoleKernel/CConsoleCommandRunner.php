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

/**
 * CConsoleCommandRunner manages commands and executes the requested command.
 *
 * @property string $scriptName The entry script name.
 * @property CConsoleCommand $command The currently active command.
 *
 */
namespace ThoughtYards\Kinetics\ConsoleKernel;

use ThoughtYards\Kinetics\Component\TYComponent;
use ThoughtYards\Kinetics\ConsoleKernel\CHelpCommand;
use ThoughtYards\Kinetics\ConsoleKernel\CConsoleCommand;

class CConsoleCommandRunner extends TYComponent
{
	public $commands=array();

	private $_scriptName;
	private $_command;

	/**
	 * Executes the requested command.
	 * @param array $args list of user supplied parameters (including the entry script name and the command name).
	 * @return integer|null application exit code returned by the command.
	 * if null is returned, application will not exit explicitly. See also {@link CConsoleApplication::processRequest()}.
	 * (return value is available since version 1.1.11)
	 */
	public function run($args)
	{
		$this->_scriptName=$args[0];
		array_shift($args);
		if(isset($args[0]))
		{
			$name=$args[0];
			array_shift($args);
		}
		else
			$name='help';

		$oldCommand=$this->_command;
		if(($command=$this->createCommand($name))===null)
			$command=$this->createCommand('help');
		$this->_command=$command;
		$command->init();
		$exitCode=$command->run($args);
		$this->_command=$oldCommand;
		return $exitCode;
	}

	/**
	 * @return string the entry script name
	 */
	public function getScriptName()
	{
		return $this->_scriptName;
	}

	/**
	 * Returns the currently running command.
	 * @return CConsoleCommand|null the currently active command.
	 * @since 1.1.14
	 */
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	 * @param CConsoleCommand $value the currently active command.
	 * @since 1.1.14
	 */
	public function setCommand($value)
	{
		$this->_command=$value;
	}

	/**
	 * Searches for commands under the specified directory.
	 * @param string $path the directory containing the command class files.
	 * @return array list of commands (command name=>command class file)
	 */
	public function findCommands($path)
	{
		if(($dir=@opendir($path))===false)
			return array();
		$commands=array();
		while(($name=readdir($dir))!==false)
		{
			$file=$path.DIRECTORY_SEPARATOR.$name;
			if(!strcasecmp(substr($name,-11),'Command.php') && is_file($file))
				$commands[strtolower(substr($name,0,-11))]=$file;
		}
		closedir($dir);
		return $commands;
	}

	/**
	 * Adds commands from the specified command path.
	 * If a command already exists, the new one will be ignored.
	 * @param string $path the alias of the directory containing the command class files.
	 */
	public function addCommands($path)
	{
		if(($commands=$this->findCommands($path))!==array())
		{
			foreach($commands as $name=>$file)
			{
				if(!isset($this->commands[$name]))
					$this->commands[$name]=$file;
			}
		}
	}

	/**
	 * @param string $name command name (case-insensitive)
	 * @return CConsoleCommand the command object. Null if the name is invalid.
	 */
	public function createCommand($name)
	{
		$name=strtolower($name);

		$command=null;
		if(isset($this->commands[$name]))
			$command=$this->commands[$name];
		else
		{
			$commands=array_change_key_case($this->commands);
			if(isset($commands[$name]))
				$command=$commands[$name];
		}

		if($command!==null)
		{
			if(is_string($command)) // class file path or alias
			{
				if(strpos($command,'/')!==false || strpos($command,'\\')!==false)
				{
					$className=substr(basename($command),0,-4);
					if(!class_exists($className,false))
						require_once($command);
				}
				else // an alias
					$className=Gateway::import($command);
				return new $className($name,$this);
			}
			else // an array configuration
				return Gateway::createComponent($command,$name,$this);
		}
		elseif($name==='help')
			return new CHelpCommand('help',$this);//TODO bring help as a integral part of shell script
		else
			return null;
	}
}