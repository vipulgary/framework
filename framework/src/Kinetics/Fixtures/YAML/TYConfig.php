<?php
/*
 * Thought Yards the Innovation
 * LICENCE - GNU -
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * This class with use as the container.
 * Pre requisite for the WEB/CONSOLE Applicationy
 * links blog:- http://thoughtyards.info/ , site:- http://thoughtyards.com/
 * Confguration File reader Format YAML
 */
namespace ThoughtYards\Kinetics\Fixtures\YAML;

use ThoughytYards\Kinetics\Fixtures\YAML\Spyc;
use ThoughtYards\Kinetics\Component\ThoughtException;
use Gateway;


//@@TODO Write the Config.YML generic Funtion. Vipul Dadhich

class TYConfig
{
	const APP_CONFIG='config';

	const APP_ROUTERS='routing';

	const APP_VALIDATIONS='validation';

	const APP_BOOTING='booting';

	const EXT='yml';

	protected $_config;

	protected $_routing;

	protected $_validation;

	protected $_ymlDump;

	public function __construct($default=false)
	{
		$this->bootconfig=$default;
		$this->router=self::APP_ROUTERS;
		$this->validation=self::APP_ROUTERS;
	}

	public function getConfig()
	{
		return $this->_config;
	}

	public function getRouters()
	{
		return $this->_router;
	}

	public function getValidation()
	{
		return $this->validation;
	}

	public function initConfig($type=null)
	{
		try{
			$configFileName=Gateway::getAppRoot().'/booting.yml';
			if(file_exists($configFileName))
			{
				if(null == $type){
					$type='booting'; //setting default as booting.yml if type is null
				}
				$this->_ymlDump=$this->LoadConfig($type, $configFileName);
				Gateway::unregister('booting');
				Gateway::register('booting', $this->tobject($this->_ymlDump));  //registering the booting config

				return $this->tobject($this->_ymlDump);
			}
			else throw new ThoughtException('YAML file '.$this->bootconfig.' does not exits');
		}
		catch (ThoughtException  $e){
			echo 'Caught exception: ', $e->getMessage(), "\n" ;
		}
	}

	/*
	 *
	 */
	public function LoadConfig($type=null, $configFileName=null)
	{
		try{
			$var=Gateway::registry('booting');
			if(null != $var)
			$configDir=Gateway::getAppRoot().$var->app->config_dir;
			else $configDir=Gateway::getAppRoot();

			if($type=='booting'){
				if(file_exists($configFileName))
				{
					$this->getDirParseContent($configDir, self::APP_BOOTING);
					return $this->_appconfig=Spyc::YAMLLoad($configFileName); //registering the yml booting records
				}
				else throw new ThoughtException('YAML file '.$this->bootconfig.' does not exits');
			}
			elseif($type=='routing'){
				$this->routing=$this->getDirParseContent($configDir, self::APP_ROUTERS);
				Gateway::register('_routing', $this->tobject($this->_routing));  //registering the yml routers
				return $this->tobject($this->_routing);
			}
			elseif($type=='validation'){
				$this->_validation=$this->getDirParseContent($configDir, self::APP_VALIDATIONS);
				Gateway::register('_validation', $this->tobject($this->_validation));  //registering the yml validation
				return $this->tobject($this->_validation);
			}
			elseif($type=='config'){
				$this->_config=$this->getDirParseContent($configDir, self::APP_CONFIG);
				//Gateway::register('_config', $this->tobject($this->_config));  //registering the yml config
				//TODO @Vipul Add condistion to take vars from registry if available
				//Optimizing loading and reading file again and again.
				
				return $this->tobject($this->_config);
			}
			else {
				return $this->getDirParseContent($configDir);
				//read-directory and include all YAML files present in it.
				if(count(scandir($configDir))>2 && file_exists($configDir))
				{
					$_yml=array();
					foreach (scandir($configDir) as $child )
					{
						if(self::EXT == substr(strrchr($child, '.'), 1))
						{
							$_yml[]=Spyc::YAMLLoad($configFileName);
						}
					}
					Gateway::register('all', $this->tobject($_yml[]));  //registering the booting config
				}
				else throw new ThoughtException('YAML file '.$configDir.' does not exits');
			}

			return $this->_ymlDump= $this->tobject($this->_ymlDump);

		}
		catch (ThoughtException  $e){
			echo 'Caught exception: ', $e->getMessage(), "\n" ;
		}
	}

	//@TODO- Call on logic from framework application to get the application path;
	private function getAppPath(){
		return $this->_ymlDump->parameters->base_url;
	}

	private function getAllYaml(){
		$this->_ymlDump->parameters->annotations;
	}

	/*
	 * convert the array toobject
	 * @params $Array
	 * @return stdObject
	 */

	private function tobject($Array){
		return json_decode(json_encode($Array), FALSE);
	}

	/*
	 * convert the array toobject
	 * @params $Array
	 * @return stdObject
	 */

	private function getDirParseContent($configFileName, $type=false){
		$_yml= array();
		if(count(scandir($configFileName))>2 )
		{
			foreach (scandir($configFileName) as $child )
			{
				if(self::EXT == substr(strrchr($child, '.'), 1))
				{
					if($type.".".self::EXT==$child)
					{
					 $_yml=Spyc::YAMLLoad($configFileName.'\\'.$child);
					 return $_yml;
					}
					else $_yml=Spyc::YAMLLoad($configFileName.'\\'.$child);

				}
			}
			return $_yml;
		}
		else throw new ThoughtException('YAML file '.$configDir.' does not exits');
	}
}