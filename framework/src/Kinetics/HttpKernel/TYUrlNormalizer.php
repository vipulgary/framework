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

use ThoughtYards\Kinetics\Component\TYComponent;
use ThoughtYards\Kinetics\HttpKernel\UrlRules;

use Gateway;

class TYUrlNormalizer extends TYComponent
{
	const CACHE_KEY='Gateway.CUrlManager.rules';
	const GET_FORMAT='get';
	const PATH_FORMAT='path';

	
	public $behaviors=array();

	private $_initialized=false;
	
	/**
	 * @var array the URL rules (pattern=>route).
	 */
	public $rules=array();
	/**
	 * @var string the URL suffix used when in 'path' format.
	 * For example, ".html" can be used so that the URL looks like pointing to a static HTML page. Defaults to empty.
	 */
	public $urlSuffix='';
	/**
	 * @var boolean whether to show entry script name in the constructed URL. Defaults to true.
	 */
	public $showScriptName=true;
	/**
	 * @var boolean whether to append GET parameters to the path info part. Defaults to true.
	 * This property is only effective when {@link urlFormat} is 'path' and is mainly used when
	 * creating URLs. When it is true, GET parameters will be appended to the path info and
	 * separate from each other using slashes. If this is false, GET parameters will be in query part.
	 */
	public $appendParams=true;
	/**
	 * @var string the GET variable name for route. Defaults to 'r'.
	 */
	public $routeVar='r';
	/**
	 * @var boolean whether routes are case-sensitive. Defaults to true. By setting this to false,
	 * the route in the incoming request will be turned to lower case first before further processing.
	 * As a result, you should follow the convention that you use lower case when specifying
	 * controller mapping ({@link CWebApplication::controllerMap}) and action mapping
	 * ({@link CController::actions}). Also, the directory names for organizing controllers should
	 * be in lower case.
	 */
	public $caseSensitive=true;
	/**
	 * @var boolean whether the GET parameter values should match the corresponding
	 * sub-patterns in a rule before using it to create a URL. Defaults to false, meaning
	 * a rule will be used for creating a URL only if its route and parameter names match the given ones.
	 * If this property is set true, then the given parameter values must also match the corresponding
	 * parameter sub-patterns. Note that setting this property to true will degrade performance.
	 * @since 1.1.0
	 */
	public $matchValue=false;
	/**
	 * @var string the ID of the cache application component that is used to cache the parsed URL rules.
	 * Defaults to 'cache' which refers to the primary cache application component.
	 * Set this property to false if you want to disable caching URL rules.
	 */
	public $cacheID='cache';
	/**
	 * @var boolean whether to enable strict URL parsing.
	 * This property is only effective when {@link urlFormat} is 'path'.
	 * If it is set true, then an incoming URL must match one of the {@link rules URL rules}.
	 * Otherwise, it will be treated as an invalid request and trigger a 404 HTTP exception.
	 * Defaults to false.
	 */
	public $useStrictParsing=false;
	/**
	 * @var string the class name or path alias for the URL rule instances. Defaults to 'CUrlRule'.
	 * If you change this to something else, please make sure that the new class must extend from
	 * {@link CBaseUrlRule} and have the same constructor signature as {@link CUrlRule}.
	 * It must also be serializable and autoloadable.
	 * @since 1.1.8
	 */
	public $urlRuleClass='UrlRules';

	private $_urlFormat=self::GET_FORMAT;
	private $_rules=array();
	private $_baseUrl;


	/**
	 * Initializes the application component.
	 */
	public function init()
	{
		//parent::init();
		//$this->normalizeRequest();
		$this->_initialized=true;
		$this->processRules();
	}

	/**
	 * Checks if this application component has been initialized.
	 * @return boolean whether this application component has been initialized (ie, {@link init()} is invoked).
	 */
	public function getIsInitialized()
	{
		return $this->_initialized;
	}
	
	/**
	 * Processes the URL rules.
	 */
	protected function processRules()
	{
		if(empty($this->rules) || $this->getUrlFormat()===self::GET_FORMAT)
		return;
		if($this->cacheID!==false && ($cache=Gateway::app()->getComponent($this->cacheID))!==null)
		{
			$hash=md5(serialize($this->rules));
			if(($data=$cache->get(self::CACHE_KEY))!==false && isset($data[1]) && $data[1]===$hash)
			{
				$this->_rules=$data[0];
				return;
			}
		}
		foreach($this->rules as $pattern=>$route)
		$this->_rules[]=$this->createUrlRule($route,$pattern);
		if(isset($cache))
		$cache->set(self::CACHE_KEY,array($this->_rules,$hash));
	}

	/**
	 * Adds new URL rules.
	 * In order to make the new rules effective, this method must be called BEFORE
	 * {@link CWebApplication::processRequest}.
	 * @param array $rules new URL rules (pattern=>route).
	 * @param boolean $append whether the new URL rules should be appended to the existing ones. If false,
	 * they will be inserted at the beginning.
	 * @since 1.1.4
	 */
	public function addRules($rules,$append=true)
	{
		if ($append)
		{
			foreach($rules as $pattern=>$route)
			$this->_rules[]=$this->createUrlRule($route,$pattern);
		}
		else
		{
			$rules=array_reverse($rules);
			foreach($rules as $pattern=>$route)
			array_unshift($this->_rules, $this->createUrlRule($route,$pattern));
		}
	}

	/**
	 * Creates a URL rule instance.
	 * The default implementation returns a CUrlRule object.
	 * @param mixed $route the route part of the rule. This could be a string or an array
	 * @param string $pattern the pattern part of the rule
	 * @return CUrlRule the URL rule instance
	 * @since 1.1.0
	 */
	protected function createUrlRule($route,$pattern)
	{
		if(is_array($route) && isset($route['class']))
		return $route;
		else
		{
			$urlRuleClass=Gateway::import($this->urlRuleClass,true);
			//@TODO make the URL calling dynamic.
			
			return new UrlRules($route,$pattern);
			//return new $urlRuleClass($route,$pattern);
					
		}
	}

	/**
	 * Constructs a URL.
	 * @param string $route the controller and the action (e.g. article/read)
	 * @param array $params list of GET parameters (name=>value). Both the name and value will be URL-encoded.
	 * If the name is '#', the corresponding value will be treated as an anchor
	 * and will be appended at the end of the URL.
	 * @param string $ampersand the token separating name-value pairs in the URL. Defaults to '&'.
	 * @return string the constructed URL
	 */
	public function createUrl($route,$params=array(),$ampersand='&')
	{
		unset($params[$this->routeVar]);
		foreach($params as $i=>$param)
		if($param===null)
		$params[$i]='';

		if(isset($params['#']))
		{
			$anchor='#'.$params['#'];
			unset($params['#']);
		}
		else
		$anchor='';
		$route=trim($route,'/');
		foreach($this->_rules as $i=>$rule)
		{
			if(is_array($rule))
			$this->_rules[$i]=$rule=Gateway::createComponent($rule);
			if(($url=$rule->createUrl($this,$route,$params,$ampersand))!==false)
			{
				if($rule->hasHostInfo)
				return $url==='' ? '/'.$anchor : $url.$anchor;
				else
				return $this->getBaseUrl().'/'.$url.$anchor;
			}
		}
		return $this->createUrlDefault($route,$params,$ampersand).$anchor;
	}

	/**
	 * Creates a URL based on default settings.
	 * @param string $route the controller and the action (e.g. article/read)
	 * @param array $params list of GET parameters
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return string the constructed URL
	 */
	protected function createUrlDefault($route,$params,$ampersand)
	{
		if($this->getUrlFormat()===self::PATH_FORMAT)
		{
			$url=rtrim($this->getBaseUrl().'/'.$route,'/');
			if($this->appendParams)
			{
				$url=rtrim($url.'/'.$this->createPathInfo($params,'/','/'),'/');
				return $route==='' ? $url : $url.$this->urlSuffix;
			}
			else
			{
				if($route!=='')
				$url.=$this->urlSuffix;
				$query=$this->createPathInfo($params,'=',$ampersand);
				return $query==='' ? $url : $url.'?'.$query;
			}
		}
		else
		{
			$url=$this->getBaseUrl();
			if(!$this->showScriptName)
			$url.='/';
			if($route!=='')
			{
				$url.='?'.$this->routeVar.'='.$route;
				if(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
				$url.=$ampersand.$query;
			}
			elseif(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
			$url.='?'.$query;
			return $url;
		}
	}

	/**
	 * Parses the user request.
	 * @param CHttpRequest $request the request application component
	 * @return string the route (controllerID/actionID) and perhaps GET parameters in path format.
	 */
	public function parseUrl($request)
	{
		if($this->getUrlFormat()===self::PATH_FORMAT)
		{
			$rawPathInfo=$request->getPathInfo();
			$pathInfo=$this->removeUrlSuffix($rawPathInfo,$this->urlSuffix);
			foreach($this->_rules as $i=>$rule)
			{
				if(is_array($rule))
				$this->_rules[$i]=$rule=Gateway::createComponent($rule);
				if(($r=$rule->parseUrl($this,$request,$pathInfo,$rawPathInfo))!==false)
				return isset($_GET[$this->routeVar]) ? $_GET[$this->routeVar] : $r;
			}
			if($this->useStrictParsing)
			throw new THoughtException("Unable to resolve the request '.$pathInfo,').");
			else
			return $pathInfo;
		}
		elseif(isset($_GET[$this->routeVar]))
		return $_GET[$this->routeVar];
		elseif(isset($_POST[$this->routeVar]))
		return $_POST[$this->routeVar];
		else
		return '';
	}

	/**
	 * Parses a path info into URL segments and saves them to $_GET and $_REQUEST.
	 * @param string $pathInfo path info
	 */
	public function parsePathInfo($pathInfo)
	{
		if($pathInfo==='')
		return;
		$segs=explode('/',$pathInfo.'/');
		$n=count($segs);
		for($i=0;$i<$n-1;$i+=2)
		{
			$key=$segs[$i];
			if($key==='') continue;
			$value=$segs[$i+1];
			if(($pos=strpos($key,'['))!==false && ($m=preg_match_all('/\[(.*?)\]/',$key,$matches))>0)
			{
				$name=substr($key,0,$pos);
				for($j=$m-1;$j>=0;--$j)
				{
					if($matches[1][$j]==='')
					$value=array($value);
					else
					$value=array($matches[1][$j]=>$value);
				}
				if(isset($_GET[$name]) && is_array($_GET[$name]))
				$value=CMap::mergeArray($_GET[$name],$value);
				$_REQUEST[$name]=$_GET[$name]=$value;
			}
			else
			$_REQUEST[$key]=$_GET[$key]=$value;
		}
	}

	/**
	 * Creates a path info based on the given parameters.
	 * @param array $params list of GET parameters
	 * @param string $equal the separator between name and value
	 * @param string $ampersand the separator between name-value pairs
	 * @param string $key this is used internally.
	 * @return string the created path info
	 */
	public function createPathInfo($params,$equal,$ampersand, $key=null)
	{
		$pairs = array();
		foreach($params as $k => $v)
		{
			if ($key!==null)
			$k = $key.'['.$k.']';

			if (is_array($v))
			$pairs[]=$this->createPathInfo($v,$equal,$ampersand, $k);
			else
			$pairs[]=urlencode($k).$equal.urlencode($v);
		}
		return implode($ampersand,$pairs);
	}

	/**
	 * Removes the URL suffix from path info.
	 * @param string $pathInfo path info part in the URL
	 * @param string $urlSuffix the URL suffix to be removed
	 * @return string path info with URL suffix removed.
	 */
	public function removeUrlSuffix($pathInfo,$urlSuffix)
	{
		if($urlSuffix!=='' && substr($pathInfo,-strlen($urlSuffix))===$urlSuffix)
		return substr($pathInfo,0,-strlen($urlSuffix));
		else
		return $pathInfo;
	}

	/**
	 * Returns the base URL of the application.
	 * @return string the base URL of the application (the part after host name and before query string).
	 * If {@link showScriptName} is true, it will include the script name part.
	 * Otherwise, it will not, and the ending slashes are stripped off.
	 */
	public function getBaseUrl()
	{
		if($this->_baseUrl!==null)
		return $this->_baseUrl;
		else
		{
			if($this->showScriptName)
			$this->_baseUrl=Gateway::app()->getRequest()->getScriptUrl();
			else
			$this->_baseUrl=Gateway::app()->getRequest()->getBaseUrl();
			return $this->_baseUrl;
		}
	}

	/**
	 * Sets the base URL of the application (the part after host name and before query string).
	 * This method is provided in case the {@link baseUrl} cannot be determined automatically.
	 * The ending slashes should be stripped off. And you are also responsible to remove the script name
	 * if you set {@link showScriptName} to be false.
	 * @param string $value the base URL of the application
	 * @since 1.1.1
	 */
	public function setBaseUrl($value)
	{
		$this->_baseUrl=$value;
	}

	/**
	 * Returns the URL format.
	 * @return string the URL format. Defaults to 'path'. Valid values include 'path' and 'get'.
	 * Please refer to the guide for more details about the difference between these two formats.
	 */
	public function getUrlFormat()
	{
		return $this->_urlFormat;
	}

	/**
	 * Sets the URL format.
	 * @param string $value the URL format. It must be either 'path' or 'get'.
	 */
	public function setUrlFormat($value)
	{
		if($value===self::PATH_FORMAT || $value===self::GET_FORMAT)
		$this->_urlFormat=$value;
		else
		throw new ThoughtException('CUrlManager.UrlFormat must be either "path" or "get".');
	}
}

