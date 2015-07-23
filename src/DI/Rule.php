<?php

namespace Aurora\DI;

use ReflectionClass;
use ReflectionMethod;

class Rule
{
	public $shared = false;
	public $hasInstance = false;
	public $reflectionable = false;
	public $hasReflectionClass = false;

	public $alias;
	public $Instance;
	public $ReflectionClass;

	public $prepare = [];
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
		$this->hasReflectionClass = true;
		$this->ReflectionClass = $ReflectionClass;

		return $this;
	}

	public function setReflectionable($reflectionable = true)
	{
		$this->reflectionable = $reflectionable;

		return $this;
	}

	public function isReflectionable()
	{
		return $this->reflectionable;
	}

	public function hasReflectionClass()
	{
		return $this->hasReflectionClass;
	}

	public function getReflectionClass()
	{
		return ($this->hasReflectionClass) ? $this->ReflectionClass : $this->ReflectionClass = new ReflectionClass($this->alias);
	}

	public function setShared($shared = true)
	{
		$this->shared = $shared;

		return $this;
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
		$this->Instance = $Instance;

		return $this;
	}

	public function getDefinition($method = "__construct")
	{
		return (isset($this->method[$method])) ? $this->method[$method] : ["parameters" => [], "dependecies" => []];
	}

	/*
		Method
	 */
	public function setParameter($parameter, $method = "__construct")
	{
		$this->method[$method]["parameters"][] = $parameter;

		return $this;
	}

	public function setKeyParameter($key, $parameter, $method = "__construct")
	{
		$this->method[$method]["parameters"][$key] = $parameter;

		return $this;
	}

	public function setParametersArray($parameters, $method = "__construct")
	{
		$this->method[$method]["parameters"] = $parameters;

		return $this;
	}

	public function getParameters($method = "__construct")
	{
		return (isset($this->method[$method]["parameters"])) ? $this->method[$method]["parameters"] : [];
	}

	public function getParameter($index, $method = "__construct")
	{
		return (isset($this->method[$method]["parameters"][$index])) ? $this->method[$method]["parameters"][$index] : [];
	}

	public function setDependenciesArray($dependencies, $method = "__construct")
	{
		$this->method[$method]["dependencies"] = $dependencies;

		return $this;
	}

	public function setDependency($dependency, $object, $method = "__construct")
	{
		$this->method[$method]["dependencies"][$dependency] = $object;

		return $this;
	}

	public function hasDependency($index, $method = "__construct")
	{
		return (isset($this->method[$method]["dependencies"][$index]));
	}

	public function getDependencies($method = "__construct")
	{
		return (isset($this->method[$method]["dependencies"])) ? $this->method[$method]["dependencies"] : [];
	}

	public function getDependency($index, $method = "__construct")
	{
		return (isset($this->method[$method]["dependencies"][$index])) ? $this->method[$method]["dependencies"][$index] : null;
	}
}
