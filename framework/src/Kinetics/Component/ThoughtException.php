<?php
/*
 * Thought Yards the Innovation
 * LICENCE - GNU -
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * Brain of the Framework
 * Pre requisite for the WEB/CONSOLE Applicationyta
 * links blog:- http://ThoughtYards.info/ , site:- http://ThoughtYards.com/
 * Main Component -Top level heifrarchy of the classes.
 */

namespace ThoughtYards\Kinetics\Component;

use ThoughtYards\Kinetics\Interfaces\TYException;
use Exception; //Extending Core Exception Class
use Gateway;

class ThoughtException extends Exception implements TYException
{
	protected $message = 'Unknown exception';     // Exception message
	private   $string;                            // Unknown
	protected $code    = 0;                       // User-defined exception code
	protected $file;                              // Source filename of exception
	protected $line;                              // Source line of exception
	private   $trace;                             // Unknown

	public function __construct($message = null, $code = 0)
	{
		if (!$message) {
			throw new $this('Unknown '. get_class($this));
		}

		//TODO @ add eventHandlers and behaviours before reachin here.
		//Seeting these config values through AOP.
		$gatewayConfig=Gateway::getAppConfig()->booting;
		$gatewayConfig->logging;
		if($gatewayConfig->logging)
		{
			$gatewayConfig->log_warning_level ? $gatewayConfig->log_warning_level : 10; //setting log level by default 10 here
 			Gateway::log($message,10,'ThoughtExeption');
		}

		parent::__construct($message, $code);

	}

	public function __toString()
	{
		return get_class($this) . " '{$this->message}' in {$this->file}({$this->line})\n"
		. "{$this->getTraceAsString()}";
	}

}
?>