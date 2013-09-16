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
 * CHelpCommand represents a console help command.
 *
 * CHelpCommand displays the available command list or the help instructions
 * about a specific command.
*/
namespace ThoughtYards\Kinetics\ConsoleKernel;

use ThoughtYards\Kinetics\ConsoleKernel\CConsoleCommand;
use Gateway;

class CHelpCommand extends CConsoleCommand
{
	/**
	 * Execute the action.
	 * @param array $args command line parameters specific for this command
	 * @return integer non zero application exit code after printing help
	 */
	public function run($args)
	{
		$runner=$this->getCommandRunner();
		$commands=$runner->commands;
		if(isset($args[0]))
			$name=strtolower($args[0]);
		if(!isset($args[0]) || !isset($commands[$name]))
		{
			if(!empty($commands))
			{
				echo "ThoughtYards command runner (based on Vipul Dadhich's v".Gateway::getVersion().")\n";
				echo "Usage: ".$runner->getScriptName()." <command-name> [parameters...]\n";
				echo "\nThe following commands are available:\n";
				$commandNames=array_keys($commands);
				sort($commandNames);
				echo ' - '.implode("\n - ",$commandNames);
				echo "\n\nTo see individual command help, use the following:\n";
				echo "   ".$runner->getScriptName()." help <command-name>\n";
			}
			else
			{
				echo "No available commands.\n";
				echo "Please define them under the following directory:\n";
				echo "\t".Gateway::app()->getCommandPath()."\n";
			}
		}
		else
			echo $runner->createCommand($name)->getHelp();
		return 1;
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return parent::getHelp().' [command-name]';
	}
}