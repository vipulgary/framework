<?php
/**
 * Thoughtyards Base Class.
 *
 * This script is meant to be a part of thoughtyards framework.
 * one of the thoughtyards core class.
 * CApplication Method
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo
 * @license GNU
 */

/**
 * CApplication is the base class for all application classes.
 *
 * if a PHP error or an uncaught exception occurs,
 * the application will switch to its error handling logic and jump to step 6 afterwards.
 */

namespace ThoughtYards\Kinetics\Component;

use ThoughtYards\Kinetics\Component\ThoughtException;
use ThoughtYards\Kinetics\Component\TYModule;
use Gateway;

abstract class CApplication extends TYModule
{
	/**
	 * @var string the application name. Defaults to 'My Application'.
	 */
	public $name='ThoughtYards FrameWork Application';
	/**
	 * @var string the charset currently used for the application. Defaults to 'UTF-8'.
	 */
	public $charset='UTF-8';
	/**
	 * @var string the language that the application is written in. This mainly refers to
	 * the language that the messages and view files are in. Defaults to 'en_us' (US English).
	 */
	public $sourceLanguage='en_us';

	private $_id;
	private $_basePath;
	private $_runtimePath;
	private $_extensionPath;
	private $_globalState;
	private $_stateChanged;
	private $_ended=false;
	private $_language;
	private $_homeUrl;


		/**
	 * Processes the request.
	 * This is the place where the actual request processing work is done.
	 * Derived classes should override this method.
	 */
	abstract public function processRequest();


	abstract public function setContainer(array $config);
	
	/**
	 * Retrieve the DiC container.
	 *
	 * @return CContainer
	 */
	abstract public function getContainer();

	/**
	 * Constructor.
	 * @param mixed $config application configuration.
	 * If a string, it is treated as the path of the file that contains the configuration;
	 * If an array, it is the actual configuration information.
	 * Please make sure you specify the {@link getBasePath basePath} property in the configuration,
	 * which should point to the directory containing all application logic, template and data.
	 * If not, the directory will be defaulted to 'protected'.
	 */
	public function __construct($config=null)
	{
		Gateway::setApplication($this);

		// set basePath at early as possible to avoid trouble
		if(is_string($config))
		$config=require($config);
		if(isset($config['basePath']))
		{
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}
		else
		$this->setBasePath('protected');

		//echo dirname($_SERVER['SCRIPT_FILENAME']); //@TODO implement logic for the multiple application loading..

		Gateway::setPathOfAlias('application',$this->getBasePath());
		Gateway::setPathOfAlias('webroot',dirname($_SERVER['SCRIPT_FILENAME']));
		if(isset($config['extensionPath']))
		{
			$this->setExtensionPath($config['extensionPath']);
			unset($config['extensionPath']);
		}
		else
		Gateway::setPathOfAlias('ext',$this->getBasePath().DIRECTORY_SEPARATOR.'extensions');
		if(isset($config['aliases']))
		{
			$this->setAliases($config['aliases']);
			unset($config['aliases']);
		}

		$this->preinit();
		//TODO @Vipul Initiate the System Handlers as IOC.
		$this->registerCoreComponents();
		$this->configure($config);
		//@TODO return dynamic Events
		$this->preloadComponents();
		$this->init();
	}


	/**
	 * Runs the application.
	 * This method loads static application components. Derived classes usually overrides this
	 * method to do more application-specific tasks.
	 * Remember to call the parent implementation so that static application components are loaded.
	 */
	public function run()
	{
		//@TODO add the event handlers.. Process the Request in Much Secure Way - Vipul 
		$this->processRequest();
		
	}

	/**
	 * Terminates the application.
	 * This method replaces PHP's exit() function by calling
	 * {@link onEndRequest} before exiting.
	 * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
	 * @param boolean $exit whether to exit the current request. This parameter has been available since version 1.1.5.
	 * It defaults to true, meaning the PHP's exit() function will be called at the end of this method.
	 */
	public function end($status=0,$exit=true)
	{
		//@TODO remove if($this->hasEventHandler('onEndRequest'))
		exit($status);
	}

	/**
	 * Raised right BEFORE the application processes the request.
	 * @param CEvent $event the event parameter
	 */
	public function onBeginRequest($event)
	{
		$this->raiseEvent('onBeginRequest',$event);
	}

	/**
	 * Raised right AFTER the application processes the request.
	 * @param CEvent $event the event parameter
	 */
	public function onEndRequest($event)
	{
		if(!$this->_ended)
		{
			$this->_ended=true;
			$this->raiseEvent('onEndRequest',$event);
		}
	}

	/**
	 * Returns the unique identifier for the application.
	 * @return string the unique identifier for the application.
	 */
	public function getId()
	{
		if($this->_id!==null)
		return $this->_id;
		else
		return $this->_id=sprintf('%x',crc32($this->getBasePath().$this->name));
	}

	/**
	 * Sets the unique identifier for the application.
	 * @param string $id the unique identifier for the application.
	 */
	public function setId($id)
	{
		$this->_id=$id;
	}

	/**
	 * Returns the root path of the application.
	 * @return string the root directory of the application. Defaults to 'protected'.
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * Sets the root directory of the application.
	 * This method can only be invoked at the begin of the constructor.
	 * @param string $path the root directory of the application.
	 * @throws CException if the directory does not exist.
	 */
	public function setBasePath($path)
	{
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
		throw new ThoughtException("Application base path ". $path ." is not a valid directory.");
	}

	/**
	 * Returns the directory that stores runtime files.
	 * @return string the directory that stores runtime files. Defaults to 'protected/runtime'.
	 */
	public function getRuntimePath()
	{
		if($this->_runtimePath!==null)
		return $this->_runtimePath;
		else
		{
			$this->setRuntimePath($this->getBasePath().DIRECTORY_SEPARATOR.'runtime');
			return $this->_runtimePath;
		}
	}

	/**
	 * Sets the directory that stores runtime files.
	 * @param string $path the directory that stores runtime files.
	 * @throws CException if the directory does not exist or is not writable
	 */
	public function setRuntimePath($path)
	{
		if(($runtimePath=realpath($path))===false || !is_dir($runtimePath) || !is_writable($runtimePath))
		throw new ThoughtException(Gateway::t('Gateway','Application runtime path "{path}" is not valid. Please make sure it is a directory writable by the Web server process.',
		array('{path}'=>$path)));
		$this->_runtimePath=$runtimePath;
	}

	/**
	 * Returns the root directory that holds all third-party extensions.
	 * @return string the directory that contains all extensions. Defaults to the 'extensions' directory under 'protected'.
	 */
	public function getExtensionPath()
	{
		return Gateway::getPathOfAlias('ext');
	}

	/**
	 * Sets the root directory that holds all third-party extensions.
	 * @param string $path the directory that contains all third-party extensions.
	 * @throws CException if the directory does not exist
	 */
	public function setExtensionPath($path)
	{
		if(($extensionPath=realpath($path))===false || !is_dir($extensionPath))
		throw new ThoughtException('Extension path '. print_r($path) . ' does not exist.');
	}

	/**
	 * Returns the language that the user is using and the application should be targeted to.
	 * @return string the language that the user is using and the application should be targeted to.
	 * Defaults to the {@link sourceLanguage source language}.
	 */
	public function getLanguage()
	{
		return $this->_language===null ? $this->sourceLanguage : $this->_language;
	}

	/**
	 * Specifies which language the application is targeted to.
	 *
	 * This is the language that the application displays to end users.
	 * If set null, it uses the {@link sourceLanguage source language}.
	 *
	 * Unless your application needs to support multiple languages, you should always
	 * set this language to null to maximize the application's performance.
	 * @param string $language the user language (e.g. 'en_US', 'zh_CN').
	 * If it is null, the {@link sourceLanguage} will be used.
	 */
	public function setLanguage($language)
	{
		$this->_language=$language;
	}

	/**
	 * Returns the time zone used by this application.
	 * This is a simple wrapper of PHP function date_default_timezone_get().
	 * @return string the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-get.php
	 */
	public function getTimeZone()
	{
		return date_default_timezone_get();
	}

	/**
	 * Sets the time zone used by this application.
	 * This is a simple wrapper of PHP function date_default_timezone_set().
	 * @param string $value the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-set.php
	 */
	public function setTimeZone($value)
	{
		date_default_timezone_set($value);
	}

	/**
	 * Returns the localized version of a specified file.
	 *
	 * The searching is based on the specified language code. In particular,
	 * a file with the same name will be looked for under the subdirectory
	 * named as the locale ID. For example, given the file "path/to/view.php"
	 * and locale ID "zh_cn", the localized file will be looked for as
	 * "path/to/zh_cn/view.php". If the file is not found, the original file
	 * will be returned.
	 *
	 * For consistency, it is recommended that the locale ID is given
	 * in lower case and in the format of LanguageID_RegionID (e.g. "en_us").
	 *
	 * @param string $srcFile the original file
	 * @param string $srcLanguage the language that the original file is in. If null, the application {@link sourceLanguage source language} is used.
	 * @param string $language the desired language that the file should be localized to. If null, the {@link getLanguage application language} will be used.
	 * @return string the matching localized file. The original file is returned if no localized version is found
	 * or if source language is the same as the desired language.
	 */
	public function findLocalizedFile($srcFile,$srcLanguage=null,$language=null)
	{
		if($srcLanguage===null)
		$srcLanguage=$this->sourceLanguage;
		if($language===null)
		$language=$this->getLanguage();
		if($language===$srcLanguage)
		return $srcFile;
		$desiredFile=dirname($srcFile).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.basename($srcFile);
		return is_file($desiredFile) ? $desiredFile : $srcFile;
	}

	/**
	 * Returns the locale instance.
	 * @param string $localeID the locale ID (e.g. en_US). If null, the {@link getLanguage application language ID} will be used.
	 * @return CLocale the locale instance
	 */
	public function getLocale($localeID=null)
	{
		return CLocale::getInstance($localeID===null?$this->getLanguage():$localeID);
	}

	/**
	 * Returns the directory that contains the locale data.
	 * @return string the directory that contains the locale data. It defaults to 'framework/i18n/data'.
	 * @since 1.1.0
	 */
	public function getLocaleDataPath()
	{
		return CLocale::$dataPath===null ? Gateway::getPathOfAlias('system.i18n.data') : CLocale::$dataPath;
	}

	/**
	 * Sets the directory that contains the locale data.
	 * @param string $value the directory that contains the locale data.
	 * @since 1.1.0
	 */
	public function setLocaleDataPath($value)
	{
		CLocale::$dataPath=$value;
	}

	/**
	 * @return CNumberFormatter the locale-dependent number formatter.
	 * The current {@link getLocale application locale} will be used.
	 */
	public function getNumberFormatter()
	{
		return $this->getLocale()->getNumberFormatter();
	}

	/**
	 * Returns the locale-dependent date formatter.
	 * @return CDateFormatter the locale-dependent date formatter.
	 * The current {@link getLocale application locale} will be used.
	 */
	public function getDateFormatter()
	{
		return $this->getLocale()->getDateFormatter();
	}

	/**
	 * Returns the database connection component.
	 * @return CDbConnection the database connection
	 */
	public function getDb()
	{
		return $this->getComponent('db');
	}

	/**
	 * Returns the error handler component.
	 * @return CErrorHandler the error handler application component.
	 */
	public function getErrorHandler()
	{
		return $this->getComponent('errorHandler');
	}

	/**
	 * Returns the security manager component.
	 * @return CSecurityManager the security manager application component.
	 */
	public function getSecurityManager()
	{
		return $this->getComponent('securityManager');
	}

	/**
	 * Returns the state persister component.
	 * @return CStatePersister the state persister application component.
	 */
	public function getStatePersister()
	{
		return $this->getComponent('statePersister');
	}

	/**
	 * Returns the cache component.
	 * @return CCache the cache application component. Null if the component is not enabled.
	 */
	public function getCache()
	{
		return $this->getComponent('cache');
	}

	/**
	 * Returns the core message translations component.
	 * @return CPhpMessageSource the core message translations
	 */
	public function getCoreMessages()
	{
		return $this->getComponent('coreMessages');
	}

	/**
	 * Returns the application message translations component.
	 * @return CMessageSource the application message translations
	 */
	public function getMessages()
	{
		return $this->getComponent('messages');
	}

	/**
	 * Returns the request component.
	 * @return CHttpRequest the request component
	 */
	public function getRequest()
	{
		return $this->getComponent('request');
	}

	/**
	 * Returns the URL manager component.
	 * @return CUrlManager the URL manager component
	 */
	public function getUrlManager()
	{
		return $this->getComponent('urlManager');
	}

	/**
	 * @return CController the currently active controller. Null is returned in this base class.
	 * @since 1.1.8
	 */
	public function getController()
	{
		return null;
	}

	/**
	 * Creates a relative URL based on the given controller and action information.
	 * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
	 * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return string the constructed URL
	 */
	public function createUrl($route,$params=array(),$ampersand='&')
	{
		return $this->getUrlManager()->createUrl($route,$params,$ampersand);
	}

	/**
	 * Creates an absolute URL based on the given controller and action information.
	 * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
	 * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
	 * @param string $schema schema to use (e.g. http, https). If empty, the schema used for the current request will be used.
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return string the constructed URL
	 */
	public function createAbsoluteUrl($route,$params=array(),$schema='',$ampersand='&')
	{
		$url=$this->createUrl($route,$params,$ampersand);
		if(strpos($url,'http')===0)
		return $url;
		else
		return $this->getRequest()->getHostInfo($schema).$url;
	}

	/**
	 * Returns the relative URL for the application.
	 * This is a shortcut method to {@link CHttpRequest::getBaseUrl()}.
	 * @param boolean $absolute whether to return an absolute URL. Defaults to false, meaning returning a relative one.
	 * @return string the relative URL for the application
	 * @see CHttpRequest::getBaseUrl()
	 */
	public function getBaseUrl($absolute=false)
	{
		return $this->getRequest()->getBaseUrl($absolute);
	}

	/**
	 * @return string the homepage URL
	 */
	public function getHomeUrl()
	{
		if($this->_homeUrl===null)
		{
			if($this->getUrlManager()->showScriptName)
			return $this->getRequest()->getScriptUrl();
			else
			return $this->getRequest()->getBaseUrl().'/';
		}
		else
		return $this->_homeUrl;
	}

	/**
	 * @param string $value the homepage URL
	 */
	public function setHomeUrl($value)
	{
		$this->_homeUrl=$value;
	}

	/**
	 * Returns a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * @param string $key the name of the value to be returned
	 * @param mixed $defaultValue the default value. If the named global value is not found, this will be returned instead.
	 * @return mixed the named global value
	 * @see setGlobalState
	 */
	public function getGlobalState($key,$defaultValue=null)
	{
		if($this->_globalState===null)
		$this->loadGlobalState();
		if(isset($this->_globalState[$key]))
		return $this->_globalState[$key];
		else
		return $defaultValue;
	}

	/**
	 * Sets a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * Make sure that the value is serializable and unserializable.
	 * @param string $key the name of the value to be saved
	 * @param mixed $value the global value to be saved. It must be serializable.
	 * @param mixed $defaultValue the default value. If the named global value is the same as this value, it will be cleared from the current storage.
	 * @see getGlobalState
	 */
	public function setGlobalState($key,$value,$defaultValue=null)
	{
		if($this->_globalState===null)
		$this->loadGlobalState();

		$changed=$this->_stateChanged;
		if($value===$defaultValue)
		{
			if(isset($this->_globalState[$key]))
			{
				unset($this->_globalState[$key]);
				$this->_stateChanged=true;
			}
		}
		elseif(!isset($this->_globalState[$key]) || $this->_globalState[$key]!==$value)
		{
			$this->_globalState[$key]=$value;
			$this->_stateChanged=true;
		}

		if($this->_stateChanged!==$changed)
		$this->attachEventHandler('onEndRequest',array($this,'saveGlobalState'));
	}

	/**
	 * Clears a global value.
	 *
	 * The value cleared will no longer be available in this request and the following requests.
	 * @param string $key the name of the value to be cleared
	 */
	public function clearGlobalState($key)
	{
		$this->setGlobalState($key,true,true);
	}

	/**
	 * Loads the global state data from persistent storage.
	 * @see getStatePersister
	 * @throws CException if the state persister is not available
	 */
	public function loadGlobalState()
	{
		$persister=$this->getStatePersister();
		if(($this->_globalState=$persister->load())===null)
		$this->_globalState=array();
		$this->_stateChanged=false;
		$this->detachEventHandler('onEndRequest',array($this,'saveGlobalState'));
	}

	/**
	 * Saves the global state data into persistent storage.
	 * @see getStatePersister
	 * @throws CException if the state persister is not available
	 */
	public function saveGlobalState()
	{
		if($this->_stateChanged)
		{
			$this->_stateChanged=false;
			$this->detachEventHandler('onEndRequest',array($this,'saveGlobalState'));
			$this->getStatePersister()->save($this->_globalState);
		}
	}


	/**
	 * Initializes the class autoloader and error handlers.
	 */
	protected function initSystemHandlers()
	{
		
	}

	/**
	 * Registers the core application components.
	 * @see setComponents
	 */
	protected function registerCoreComponents()
	{
		$components=array(
			'db'=>array(
				'class'=>'Db',
		),
			'messages'=>array(
				'class'=>'PhpMessage',
		),
			'errorHandler'=>array(
				'class'=>'ErrorHandler',
		),
			'securityManager'=>array(
				'class'=>'SecurityManager',
		),
			'statePersister'=>array(
				'class'=>'StatePersister',
		),
			'urlManager'=>array(
				'class'=>'TYUrlNormalizer',
		),
			'request'=>array(
				'class'=>'TYRequest',
		),
			'format'=>array(
				'class'=>'TYFormatter',
		),
		);

		$this->setComponents($components);
	}
}
