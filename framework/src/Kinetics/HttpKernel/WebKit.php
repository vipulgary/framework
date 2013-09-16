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


namespace ThoughtYards\Kinetics\HttpKernel;

use Thoughtyards\Kinetics\Component\Controller;

use ThoughtYards\Kinetics\Component\ThoughtException;
use ThoughtYards\Kinetics\Component\TYModule;
use ThoughtYards\Kinetics\Component\CApplication;
use ThoughtYards\Fixtures\DependencyInjection\TYContainer;
use Gateway;

class WebKit extends CApplication
{

	/**
	 * @var \IOC dependency injection container
	 */
	public $_container = null;


	/**
	 * @return string the route of the default controller, action or module. Defaults to 'site'.
	 */
	public $defaultController='terminal';
	/**
	 * @var mixed the application-wide layout. Defaults to 'main' (relative to {@link getLayoutPath layoutPath}).
	 * If this is false, then no layout will be used.
	 */
	public $layout='main';
	
	public $controllerMap=array();
	/**
	 * @var array the configuration specifying a controller which should handle
	 * all user requests. This is mainly used when the application is in maintenance mode
	 * and we should use a controller to handle all incoming requests.
	 * The configuration specifies the controller route (the first element)
	 * and GET parameters (the rest name-value pairs). For example,
	 * <pre>
	 * array(
	 *     'offline/notice',
	 *     'param1'=>'value1',
	 *     'param2'=>'value2',
	 * )
	 * </pre>
	 * Defaults to null, meaning catch-all is not effective.
	 */
	public $catchAllRequest;

	/**
	 * @var string Namespace that should be used when loading controllers.
	 * Default is to use global namespace.
	 * @since 1.1.11
	 */
	public $controllerNamespace;

	private $_controllerPath;
	private $_viewPath;
	private $_systemViewPath;
	private $_layoutPath;
	private $_controller;
	private $_theme;

	
	public function setContainer(array $config)
	{
		$container = isset($config['class']) && !empty($config['class']) ? $config['class'] : 'TYContainer';
		$services = isset($config['services']) && is_array($config['services']) && !empty($config['services'])
		? $config['services']
		: array();
		$this->_container = new TYContainer($services);		
	}

	/**
	 * Retrieve the DiC container.
	 *
	 * @return CContainer
	 */
	public function getContainer()
	{
		if ($this->_container === null) {
			$this->_container = new TYContainer();
		}
		return $this->_container;
	}

	/**
	 * Processes the current request.
	 * It first resolves the request into controller and action,
	 * and then creates the controller to perform the action.
	 */
	public function processRequest()
	{
		if(is_array($this->catchAllRequest) && isset($this->catchAllRequest[0]))
		{

			$route=$this->catchAllRequest[0];
			foreach(array_splice($this->catchAllRequest,1) as $name=>$value)
			$_GET[$name]=$value;
		}
		else{
			$route=$this->getUrlManager()->parseUrl($this->getRequest());
		}

		//TODO request vipul routers

		$this->runController($route);
	}

	/**
	 * Registers the core application components.
	 * This method overrides the parent implementation by registering additional core components.
	 * @see setComponents
	 */
	protected function registerCoreComponents()
	{
		parent::registerCoreComponents();

		$components=array(
			'session'=>array(
				'class'=>'CHttpSession',
		),
			'assetManager'=>array(
				'class'=>'CAssetManager',
		),
			'user'=>array(
				'class'=>'CWebUser',
		),
			'themeManager'=>array(
				'class'=>'CThemeManager',
		),
			'authManager'=>array(
				'class'=>'CPhpAuthManager',
		),
			'clientScript'=>array(
				'class'=>'CClientScript',
		),
			'widgetFactory'=>array(
				'class'=>'CWidgetFactory',
		),
		);

		$this->setComponents($components);
	}

	/**
	 * @return IAuthManager the authorization manager component
	 */
	public function getAuthManager()
	{
		return $this->getComponent('authManager');
	}

	/**
	 * @return CAssetManager the asset manager component
	 */
	public function getAssetManager()
	{
		return $this->getComponent('assetManager');
	}

	/**
	 * @return CHttpSession the session component
	 */
	public function getSession()
	{
		return $this->getComponent('session');
	}

	/**
	 * @return CWebUser the user session information
	 */
	public function getUser()
	{
		return $this->getComponent('user');
	}

	/**
	 * Returns the view renderer.
	 * If this component is registered and enabled, the default
	 * view rendering logic defined in {@link CBaseController} will
	 * be replaced by this renderer.
	 * @return IViewRenderer the view renderer.
	 */
	public function getViewRenderer()
	{
		return $this->getComponent('viewRenderer');
	}

	/**
	 * Returns the client script manager.
	 * @return CClientScript the client script manager
	 */
	public function getClientScript()
	{
		return $this->getComponent('clientScript');
	}

	/**
	 * Returns the widget factory.
	 * @return IWidgetFactory the widget factory
	 * @since 1.1
	 */
	public function getWidgetFactory()
	{
		return $this->getComponent('widgetFactory');
	}

	/**
	 * @return CThemeManager the theme manager.
	 */
	public function getThemeManager()
	{
		return $this->getComponent('themeManager');
	}

	/**
	 * @return CTheme the theme used currently. Null if no theme is being used.
	 */
	public function getTheme()
	{
		if(is_string($this->_theme))
		$this->_theme=$this->getThemeManager()->getTheme($this->_theme);
		return $this->_theme;
	}

	/**
	 * @param string $value the theme name
	 */
	public function setTheme($value)
	{
		$this->_theme=$value;
	}

	/**
	 * Creates the controller and performs the specified action.
	 * @param string $route the route of the current request. See {@link createController} for more details.
	 * @throws CHttpException if the controller could not be created.
	 */
	public function runController($route)
	{
		if(($ca=$this->createController($route))!==null)
		{
			list($controller,$actionID)=$ca;
			$oldController=$this->_controller;
			$this->_controller=$controller;
			$controller->init();
			$controller->run($actionID);
			$this->_controller=$oldController;
		}
		else
		throw new ThoughtException('Unable to resolve the request '.empty($route)?$this->defaultController:$route);

	}

	/**
	 * Creates a controller instance based on a route.
	 * The route should contain the controller ID and the action ID.
	 * It may also contain additional GET variables. All these must be concatenated together with slashes.
	 *
	 * This method will attempt to create a controller in the following order:
	 * <ol>
	 * <li>If the first segment is found in {@link controllerMap}, the corresponding
	 * controller configuration will be used to create the controller;</li>
	 * <li>If the first segment is found to be a module ID, the corresponding module
	 * will be used to create the controller;</li>
	 * <li>Otherwise, it will search under the {@link controllerPath} to create
	 * the corresponding controller. For example, if the route is "admin/user/create",
	 * then the controller will be created using the class file "protected/controllers/admin/UserController.php".</li>
	 * </ol>
	 * @param string $route the route of the request.
	 * @param CWebModule $owner the module that the new controller will belong to. Defaults to null, meaning the application
	 * instance is the owner.
	 * @return array the controller instance and the action ID. Null if the controller class does not exist or the route is invalid.
	 */
	public function createController($route,$owner=null)
	{
		if($owner===null)
		$owner=$this;
		if(($route=trim($route,'/'))==='')
		$route=$owner->defaultController;
		$caseSensitive=$this->getUrlManager()->caseSensitive;

		$route.='/';
		while(($pos=strpos($route,'/'))!==false)
		{
			$id=substr($route,0,$pos);
			if(!preg_match('/^\w+$/',$id))
			return null;
			if(!$caseSensitive)
			$id=strtolower($id);
			$route=(string)substr($route,$pos+1);
			if(!isset($basePath))  // first segment
			{
				if(isset($owner->controllerMap[$id]))
				{
					return array(
					Gateway::createComponent($owner->controllerMap[$id],$id,$owner===$this?null:$owner),
					$this->parseActionParams($route),
					);
				}

				if(($module=$owner->getModule($id))!==null)
				return $this->createController($route,$module);

				$basePath=$owner->getControllerPath();
				$controllerID='';
			}
			else
			$controllerID.='/';
			$className=ucfirst($id).'Controller';
			$classFile=$basePath.DIRECTORY_SEPARATOR.$className.'.php';
			//@TODO Vipul Make the modification over the Contollers name.

			if($owner->controllerNamespace!==null)
			$className=$owner->controllerNamespace.'\\'.$className;



			if(is_file($classFile))
			{
				if(!class_exists($className,false))
				require($classFile);
					
				$className='TerminalKit\Payroll\controllers\\'.$className;
				//@TODO remove patch From here and make it specific to configutaion YML file
				if(class_exists($className,false))
				{
					$id[0]=strtolower($id[0]);
					return array(
					new $className($controllerID.$id,$owner===$this?null:$owner),
					$this->parseActionParams($route),
					);
				}
				return null;
			}
			$controllerID.=$id;
			$basePath.=DIRECTORY_SEPARATOR.$id;

		}
	}

	/**
	 * Parses a path info into an action ID and GET variables.
	 * @param string $pathInfo path info
	 * @return string action ID
	 */
	protected function parseActionParams($pathInfo)
	{
		if(($pos=strpos($pathInfo,'/'))!==false)
		{
			$manager=$this->getUrlManager();
			$manager->parsePathInfo((string)substr($pathInfo,$pos+1));
			$actionID=substr($pathInfo,0,$pos);
			return $manager->caseSensitive ? $actionID : strtolower($actionID);
		}
		else
		return $pathInfo;
	}

	/**
	 * @return CController the currently active controller
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * @param CController $value the currently active controller
	 */
	public function setController($value)
	{
		$this->_controller=$value;
	}

	/**
	 * @return string the directory that contains the controller classes. Defaults to 'protected/controllers'.
	 */
	public function getControllerPath()
	{
		if($this->_controllerPath!==null)
		return $this->_controllerPath;
		else
		return $this->_controllerPath=$this->getBasePath().DIRECTORY_SEPARATOR.'controllers';
	}

	/**
	 * @param string $value the directory that contains the controller classes.
	 * @throws CException if the directory is invalid
	 */
	public function setControllerPath($value)
	{
		if(($this->_controllerPath=realpath($value))===false || !is_dir($this->_controllerPath))
		throw new ThoughtException("The controller path '.$value.' is not a valid directory.");
		
	}

	/**
	 * @return string the root directory of view files. Defaults to 'protected/views'.
	 */
	public function getViewPath()
	{
		if($this->_viewPath!==null)
		return $this->_viewPath;
		else
		return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'views';
	}

	/**
	 * @param string $path the root directory of view files.
	 * @throws CException if the directory does not exist.
	 */
	public function setViewPath($path)
	{
		if(($this->_viewPath=realpath($path))===false || !is_dir($this->_viewPath))
		throw new ThoughtException("The controller path '.$path.' is not a valid directory.");
	}

	/**
	 * @return string the root directory of system view files. Defaults to 'protected/views/system'.
	 */
	public function getSystemViewPath()
	{
		if($this->_systemViewPath!==null)
		return $this->_systemViewPath;
		else
		return $this->_systemViewPath=$this->getViewPath().DIRECTORY_SEPARATOR.'system';
	}

	/**
	 * @param string $path the root directory of system view files.
	 * @throws CException if the directory does not exist.
	 */
	public function setSystemViewPath($path)
	{
		if(($this->_systemViewPath=realpath($path))===false || !is_dir($this->_systemViewPath))
		throw new ThoughtException("The system view  '.$path.' is not a valid directory.");		
	}

	/**
	 * @return string the root directory of layout files. Defaults to 'protected/views/layouts'.
	 */
	public function getLayoutPath()
	{
		if($this->_layoutPath!==null)
		return $this->_layoutPath;
		else
		return $this->_layoutPath=$this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
	}

	/**
	 * @param string $path the root directory of layout files.
	 * @throws CException if the directory does not exist.
	 */
	public function setLayoutPath($path)
	{
		if(($this->_layoutPath=realpath($path))===false || !is_dir($this->_layoutPath))
		throw new ThoughtException("The layout path '.$path.' is not a valid directory.");	
		
	}

	/**
	 * The pre-filter for controller actions.
	 * This method is invoked before the currently requested controller action and all its filters
	 * are executed. You may override this method with logic that needs to be done
	 * before all controller actions.
	 * @param CController $controller the controller
	 * @param CAction $action the action
	 * @return boolean whether the action should be executed.
	 */
	public function beforeControllerAction($controller,$action)
	{
		return true;
	}

	/**
	 * The post-filter for controller actions.
	 * This method is invoked after the currently requested controller action and all its filters
	 * are executed. You may override this method with logic that needs to be done
	 * after all controller actions.
	 * @param CController $controller the controller
	 * @param CAction $action the action
	 */
	public function afterControllerAction($controller,$action)
	{
	}

	/**
	 * Do not call this method. This method is used internally to search for a module by its ID.
	 * @param string $id module ID
	 * @return CWebModule the module that has the specified ID. Null if no module is found.
	 */
	public function findModule($id)
	{
		if(($controller=$this->getController())!==null && ($module=$controller->getModule())!==null)
		{
			do
			{
				if(($m=$module->getModule($id))!==null)
				return $m;
			} while(($module=$module->getParentModule())!==null);
		}
		if(($m=$this->getModule($id))!==null)
		return $m;
	}

	/**
	 * Initializes the application.
	 * This method overrides the parent implementation by preloading the 'request' component.
	 */
	protected function init()
	{
		parent::init();
		// preload 'request' so that it has chance to respond to onBeginRequest event.
		$this->getRequest();
	}
}
