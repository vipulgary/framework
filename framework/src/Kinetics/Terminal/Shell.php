<?php
/**
 * Thoughtyards - Mikko Test by Vipul.
 *
 * This script is meant to be run on command line to execute
 * one of the pre-defined console commands.
 *
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo - Demo
 * @license GNU
 */

namespace ThoughtYards\Kinetics\Terminal;

class Shell
{
	function phpTerm()
	{} // constructor

	function formatPrompt()
	{
		$user=shell_exec("whoami");
		$host=explode(".", shell_exec("uname -n"));
		$_SESSION['prompt'] = "".rtrim($user).""."@"."".rtrim($host[0])."";
	}

	function checkPassword($passwd)
	{
		if(!isset($_SERVER['PHP_AUTH_USER'])||
		!isset($_SERVER['PHP_AUTH_PW']) ||
		!isset($passwd[$_SERVER['PHP_AUTH_USER']]) ||
		$passwd[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW'])
		{
			@session_destroy();
			return false;
		}
		else
		{
			@session_start();
			return true;
		}
	}

	function logout($logout)
	{
		if($logout==true){

			header('WWW-Authenticate: Basic realm="PHP-Terminal"');
			header('HTTP/1.0 401 Unauthorized');
			exit();
		}
	}

	function phpCheckVersion($min_version)
	{
		$is_version=phpversion();

		list($v1,$v2,$v3,$v4) = sscanf($is_version,"%d.%d.%d%s");
		list($m1,$m2,$m3,$m4) = sscanf($min_version,"%d.%d.%d%s");

		if($v1>$m1)
		return(1);
		elseif($v1<$m1)
		return(0);
		if($v2>$m2)
		return(1);
		elseif($v2<$m2)
		return(0);
		if($v3>$m3)
		return(1);
		elseif($v3<$m3)
		return(0);

		if((!$v4)&&(!$m4))
		return(1);
		if(($v4)&&(!$m4))
		{
			$is_version=strpos($v4,"pl");
			if(is_integer($is_version))
			return(1);
			return(0);
		}
		elseif((!$v4)&&($m4))
		{
			$is_version=strpos($m4,"rc");
			if(is_integer($is_version))
			return(1);
			return(0);
		}
		return(0);
	}

	function initVars()
	{
		if (empty($_SESSION['cwd']) || @!empty($_GET['reset']))
		{
			$_SESSION['cwd'] = getcwd();
			$_SESSION['history'] = array();
			$_SESSION['output'] = '';
			$_REQUEST['command'] ='';
			$_SESSION['color'] = 'linux';
		}
	}

	function buildCommandHistory()
	{
		if(!empty($_REQUEST['command']))
		{
			if(get_magic_quotes_gpc())
			{
				$_REQUEST['command'] = stripslashes($_REQUEST['command']);
			}

			// drop old commands from list if exists
			if (($i = array_search($_REQUEST['command'], $_SESSION['history'])) !== false)
			{
				unset($_SESSION['history'][$i]);
			}
			array_unshift($_SESSION['history'], $_REQUEST['command']);

			// append commmand */
			$_SESSION['output'] .= "{$_SESSION['prompt']}".":>"."{$_REQUEST['command']}"."\n";
		}
	}

	function buildJavaHistory()
	{
		// build command history for use in the JavaScript
		if (empty($_SESSION['history']))
		{
			$_SESSION['js_command_hist'] = '""';
		}
		else
		{
			$escaped = array_map('addslashes', $_SESSION['history']);
			$_SESSION['js_command_hist'] = '"", "' . implode('", "', $escaped) . '"';
		}
	}

	function setTerminalColor($color)
	{
		$color="green";
		$_SESSION['color']="$color";

		// terminal colors
		switch($color)
		{
			case "linux":
				{
					echo "<style>textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #CCCCCC;}</style>";
					break;
				}
			case "green":
				{
					echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #00C000; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #00C000;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #00C000;}</style>";
					break;
				}
			case "black":
				{
					echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #000000; background-color: #00C000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #00C000; color: #000000;}
		input.prompt {border: none; font-family: monospace; background-color: #00C000; color: #000000;}</style>";
					break;
				}
			case "gray":
				{
					echo "<style>
		textarea {width: 99.5%; border: none; margin: 0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #0000FF;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #0000FF; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #0000FF; color: #CCCCCC;}</style>";
					break;
				}
			default:
				{
					echo "<style>textarea {width: 99.5%; border: none; margin:0px; padding: 2px 2px 2px; color: #CCCCCC; background-color: #000000;}
		p {font-family: monospace; margin: 0px; padding: 0px 2px 2px; background-color: #000000; color: #CCCCCC;}
		input.prompt {border: none; font-family: monospace; background-color: #000000; color: #CCCCCC;}</style>";
					break;
				}
		}
	}

	function outputHandle($aliases)
	{
		if (ereg('^[[:blank:]]*cd[[:blank:]]*$', @$_REQUEST['command']))
		{
			$_SESSION['cwd'] = getcwd(); //dirname(__FILE__);
		}
		elseif(ereg('^[[:blank:]]*cd[[:blank:]]+([^;]+)$', @$_REQUEST['command'], $regs))
		{
			// The current command is 'cd', which we have to handle as an internal shell command.
			// absolute/relative path ?"
			($regs[1][0] == '/') ? $new_dir = $regs[1] : $new_dir = $_SESSION['cwd'] . '/' . $regs[1];

			// cosmetics
			while (strpos($new_dir, '/./') !== false)
			$new_dir = str_replace('/./', '/', $new_dir);
			while (strpos($new_dir, '//') !== false)
			$new_dir = str_replace('//', '/', $new_dir);
			while (preg_match('|/\.\.(?!\.)|', $new_dir))
			$new_dir = preg_replace('|/?[^/]+/\.\.(?!\.)|', '', $new_dir);

			if(empty($new_dir)): $new_dir = "/"; endif;

			(@chdir($new_dir)) ? $_SESSION['cwd'] = $new_dir : $_SESSION['output'] .= "could not change to: $new_dir\n";
		}
		else
		{
			/* The command is not a 'cd' command, so we execute it after
			 changing the directory and save the output. */
			chdir($_SESSION['cwd']);

			/* Alias expansion. */
			$length = strcspn(@$_REQUEST['command'], " \t");
			$token = substr(@$_REQUEST['command'], 0, $length);
			if (isset($aliases[$token]))
			$_REQUEST['command'] = $aliases[$token] . substr($_REQUEST['command'], $length);

				
			if($this->phpCheckVersion("4.3.0"))
			{
				$p = proc_open(@$_REQUEST['command'],
				array(1 => array('pipe', 'w'),
				2 => array('pipe', 'w')), $io);

				/* Read output sent to stdout. */
				while (!feof($io[1])) {
					$_SESSION['output'] .= htmlspecialchars(fgets($io[1]),ENT_COMPAT, 'UTF-8');
				}
				/* Read output sent to stderr. */
				while (!feof($io[2])) {
					$_SESSION['output'] .= htmlspecialchars(fgets($io[2]),ENT_COMPAT, 'UTF-8');
				}
					
				fclose($io[1]);
				fclose($io[2]);
				proc_close($p);
			}
			else
			{
				$stdout=shell_exec($_REQUEST['command']);
				$_SESSION['output'] .= htmlspecialchars($stdout,ENT_COMPAT, 'UTF-8');
			}
		}
	}
}


