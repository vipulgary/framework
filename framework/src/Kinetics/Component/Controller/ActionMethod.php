<?php
/**
 * Thoughtyards Base Class.
 *
 * This script is meant to be run as a part of thoughtyards framework
 * one of the Framewok classes.
 *
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo
 * @license GNU
 */

/**
 * ActionMethod is the base class for all controller action classes.
 *
 * ActionMethod provides a way to divide a complex controller into
 * smaller actions in separate class files.
 */

namespace ThoughtYards\Kinetics\Component\Controller;

use ThoughtYards\Kinetics\Component\ThoughtException;
use ThoughtYards\Kinetics\Component\TYComponent;
use Gateway;
use ReflectionMethod;

class ActionMethod extends TYComponent
{
	private $_id;
	private $_controller;

	/**
	 * Constructor.
	 * @param CController $controller the controller who owns this action.
	 * @param string $id id of the action.
	 */
	public function __construct($controller,$id)
	{
		$this->_controller=$controller;
		$this->_id=$id;
	}

	/**
	 * @return CController the controller who owns this action.
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * @return string id of this action
	 */
	public function getId()
	{
		return $this->_id;
	}


	/**
	 * Runs the action.
	 * The action method defined in the controller is invoked.
	 */
	public function run()
	{
		$method='action'.$this->getId();
		$this->getController()->$method();
	}

	/**
	 * Runs the action with the supplied request parameters.
	 * This method is internally called by {@link Controller::runAction()}.
	 * @param array $params the request parameters (name=>value)
	 * @return boolean whether the request parameters are valid
	 */
	public function runWithParams($params)
	{
		$methodName='action'.$this->getId();
		$controller=$this->getController();
		$method=new ReflectionMethod($controller, $methodName);
		if($method->getNumberOfParameters()>0)
		return $this->runWithParamsInternal($controller, $method, $params);
		else
		return $controller->$methodName();
	}

	/**
	 * Executes a method of an object with the supplied named parameters.
	 * This method is internally used.
	 * @param mixed $object the object whose method is to be executed
	 * @param ReflectionMethod $method the method reflection
	 * @param array $params the named parameters
	 * @return boolean whether the named parameters are valid
	 */
	protected function runWithParamsInternal($object, $method, $params)
	{
		$ps=array();
		foreach($method->getParameters() as $i=>$param)
		{
			$name=$param->getName();
			if(isset($params[$name]))
			{
				if($param->isArray())
				$ps[]=is_array($params[$name]) ? $params[$name] : array($params[$name]);
				elseif(!is_array($params[$name]))
				$ps[]=$params[$name];
				else
				return false;
			}
			elseif($param->isDefaultValueAvailable())
			$ps[]=$param->getDefaultValue();
			else
			return false;
		}
		$method->invokeArgs($object,$ps);
		return true;
	}
}
