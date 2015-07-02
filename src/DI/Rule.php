<?php

namespace Aurora\DI;

use ReflectionClass;
use ReflectionMethod;

class Rule
{
	public $shared = false;
	public $hasInstance = false;
	public $reflectionable = false;

	public $alias;
	public $Instance;
	public $ReflectionClass;

	public $object = [
		"parameters" => [],
		"dependencies" => [],
	];

	public $method = [];

	public function __construct($alias)
	{
		$this->alias = $alias;
	}

	public function getName()
	{
		return $this->alias;
	}

	public function getAlias()
	{
		return $this->alias;
	}

	public function setReflectionClass(ReflectionClass $ReflectionClass)
	{
		$this->ReflectionClass = $ReflectionClass;
	}

	public function setReflectionMethod(ReflectionMethod $ReflectionMethod)
	{
		$this->ReflectionMethod = $ReflectionMethod;
	}

	public function setReflectionable($reflectionable = true)
	{
		return $this->reflectionable = $reflectionable;
	}

	public function isReflectionable()
	{
		return $this->reflectionable;
	}

	public function hasReflectionClass()
	{
		return isset($this->ReflectionClass);
	}

	public function getReflectionClass()
	{
		return (isset($this->ReflectionClass)) ? $this->ReflectionClass : $this->ReflectionClass = new ReflectionClass($this->alias);
	}

	public function setShared($shared = true)
	{
		$this->shared = $shared;
	}

	public function isShared()
	{
		return $this->shared;
	}

	public function hasInstance()
	{
		return $this->hasInstance;
	}

	public function getInstance()
	{
		return $this->Instance;
	}

	public function setInstance($Instance)
	{
		$this->hasInstance = true;
		return $this->Instance = $Instance;
	}

	/*
		Class
	 */
	public function setClassParameter($parameter)
	{
		$this->object["parameters"][] = $parameter;
	}

	public function setClassParametersArray($parameters)
	{
		$this->object["parameters"] = $parameters;
	}

	public function getClassParameters()
	{
		return $this->object["parameters"];
	}

	public function getClassParameter($index)
	{
		return (isset($this->object["parameters"][$index])) ? $this->object["parameters"][$index] : [] ;
	}

	public function setClassDependenciesArray($dependencies)
	{
		$this->object["dependencies"] = $dependencies;
	}

	public function setClassDependency($dependency, $object)
	{
		$this->object["dependencies"][$dependency] = $object;
	}

	public function hasClassDependency($index)
	{
		return (isset($this->object["dependencies"][$index]));
	}

	public function getClassDependencies()
	{
		return $this->object["dependencies"];
	}

	public function getClassDependency($index)
	{
		return (isset($this->object["dependencies"][$index])) ? $this->object["dependencies"][$index] : [];
	}

	/*
		Method
	 */
	public function setMethodParameter($parameter, $method = "")
	{
		$this->method[$method]["parameters"][] = $parameter;
	}

	public function setMethodParametersArray($parameters, $method = "")
	{
		$this->method[$method]["parameters"] = $parameters;
	}

	public function getMethodParameters($method = "")
	{
		return $this->method[$method]["parameters"];
	}

	public function getMethodParameter($index, $method = "")
	{
		return (isset($this->method[$method]["parameters"][$index])) ? $this->method[$method]["parameters"][$index] : [] ;
	}

	public function setMethodDependenciesArray($dependencies, $method = "")
	{
		$this->method[$method]["dependencies"] = $dependencies;
	}

	public function setMethodDependency($dependency, $object, $method = "")
	{
		$this->method[$method]["dependencies"][$dependency] = $object;
	}

	public function hasMethodDependency($index, $method = "")
	{
		return (isset($this->method[$method]["dependencies"][$index]));
	}

	public function getMethodDependencies($method = "")
	{
		return $this->method[$method]["dependencies"];
	}

	public function getMethodDependency($index, $method = "")
	{
		return (isset($this->method[$method]["dependencies"][$index])) ? $this->method[$method]["dependencies"][$index] : null;
	}

	/*
		Get Parameters
	 */
	public function getParameters($type = "object", $method = null)
	{
		if ($method !== null) {
			return (isset($this->{$type}[$method])) ? $this->{$type}[$method] : [];
		} else {
			return $this->{$type};
		}
	}
}
