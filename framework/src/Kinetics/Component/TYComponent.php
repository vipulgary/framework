<?php
/*
 * Thought Yards the Innovation
 * Main Component -Top level heifrarchy of the classes.
 * LICENCE - GNU -
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * Brain of the Framework
 * Pre requisite for the WEB/CONSOLE Application
 */

namespace ThoughtYards\Kinetics\Component;

use ThoughtYards\Kinetics\Component\ThoughtException;
use Gateway;

class TYComponent
{
	private $_vars;
	private $_methods;
	private $_e;
	private $_m;

	/**
	 * Returns a property value, an event handler list or a behavior based on its name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property or obtain event handlers:
	 * <pre>
	 * $value=$component->propertyName;
	 * $handlers=$component->eventName;
	 * </pre>
	 * @param string $name the property name or event name
	 * @return mixed the property value, event handlers attached to the event, or the named behavior
	 * @throws Exception if the property or event is not defined
	 * @see __set
	 */
	
	public function getGateWayApp()
	{
		$gateWay= new Gateway();
		return $gateWay->app();
	}


	public function getGateWay()
	{
		return new Gateway();
	}
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
		return $this->$getter();
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// duplicating getEventHandlers() here for performance
			$name=strtolower($name);
			if(!isset($this->_vars[$name]))
			$this->_vars[$name]=new CList;
			return $this->_vars[$name];
		}
		elseif(isset($this->_methods[$name]))
		return $this->_methods[$name];
		elseif(is_array($this->_methods))
		{
			foreach($this->_methods as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canGetProperty($name)))
				return $object->$name;
			}
		}
		throw new ThoughtException('Property '.get_class($this).$name.' is not defined.');
			
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property or attach an event handler
	 * <pre>
	 * $this->propertyName=$value;
	 * $this->eventName=$callback;
	 * </pre>
	 * @param string $name the property name or the event name
	 * @param mixed $value the property value or callback
	 * @return mixed
	 * @throws Exception if the property/event is not defined or the property is read only.
	 * @see __get
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
		return $this->$setter($value);
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// duplicating getEventHandlers() here for performance
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
			$this->_e[$name]=new CList;
			return $this->_e[$name]->add($value);
		}
		elseif(is_array($this->_m))
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canSetProperty($name)))
				return $object->$name=$value;
			}
		}
		if(method_exists($this,'get'.$name))
		throw new ThoughtException('Property '.get_class($this)." ".$name. ' is not defined.');
		else
		throw new ThoughtException('Property '.get_class($this) ." ".$name. ' is not defined.');

	}

	/**
	 * Checks if a property value is null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using isset() to detect if a component property is set or not.
	 * @param string $name the property name or the event name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
		return $this->$getter()!==null;
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			$name=strtolower($name);
			return isset($this->_vars[$name]) && $this->_vars[$name]->getCount();
		}
		elseif(is_array($this->_methods))
		{
			if(isset($this->_methods[$name]))
			return true;
			foreach($this->_methods as $object)
			{
				if($object->getEnabled() && (property_varsxists($object,$name) || $object->canGetProperty($name)))
				return $object->$name!==null;
			}
		}
		return false;
	}

	/**
	 * Sets a component property to be null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using unset() to set a component property to be null.
	 * @param string $name the property name or the event name
	 * @throws Exception if the property is read only.
	 * @return mixed
	 */
	public function __unset($name)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
		$this->$setter(null);
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		unset($this->_vars[strtolower($name)]);
		elseif(is_array($this->_methods))
		{
			if(isset($this->_methods[$name]))
			$this->detachBehavior($name);
			else
			{
				foreach($this->_methods as $object)
				{
					if($object->getEnabled())
					{
						if(property_varsxists($object,$name))
						return $object->$name=null;
						elseif($object->canSetProperty($name))
						return $object->$setter(null);
					}
				}
			}
		}
		elseif(method_exists($this,'get'.$name))
		throw new ThoughtException('Property '.get_class($this) .$name. ' is not defined.');
	}

	/**
	 * Calls the named method which is not a class method.
	 * Do not call this method. This is a PHP magic method that we override
	 * to implement the behavior feature.
	 * @param string $name the method name
	 * @param array $parameters method parameters
	 * @throws Exception if current class and its behaviors do not have a method or closure with the given name
	 * @return mixed the method return value
	 */
	public function __call($name,$parameters)
	{
		if($this->_methods!==null)
		{
			foreach($this->_methods as $object)
			{
				if($object->getEnabled() && method_exists($object,$name))
				return call_user_func_array(array($object,$name),$parameters);
			}
		}
		if(class_exists('Closure', false) && $this->canGetProperty($name) && $this->$name instanceof Closure)
		return call_user_func_array($this->$name, $parameters);
		throw new ThoughtException(get_class($this).' and its behaviors do not have a method or closure named '.$name);
	}


	/**
	 * Determines whether a property is defined.
	 * A property is defined if there is a getter or setter method
	 * defined in the class. Note, property names are case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property is defined
	 * @see canGetProperty
	 * @see canSetProperty
	 */

	public function hasProperty($name)
	{
		return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
	}

	/**
	 * Determines whether a property can be read.
	 * A property can be read if the class has a getter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property can be read
	 * @see canSetProperty
	 */
	public function canGetProperty($name)
	{
		return method_exists($this,'get'.$name);
	}

	/**
	 * Determines whether a property can be set.
	 * A property can be written if the class has a setter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property can be written
	 * @see canGetProperty
	 */
	public function canSetProperty($name)
	{
		return method_exists($this,'set'.$name);
	}

}