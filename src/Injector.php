<?php

namespace Aurora;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;

use Aurora\DI\ResolverInterface;
use Aurora\DI\Rule;
use Aurora\DI\RuleCollection;

class Injector implements ResolverInterface
{

	public function saveReflection($alias)
	{
		RuleCollection::getRule($alias, true)->reflectionable = true;
	}

	public function bind($alias, $binding)
	{
		RuleCollection::$map[$alias] = $binding;
	}

	public function alias($alias, $binding)
	{
		RuleCollection::$map[$alias] = $binding;
	}

	public function share($alias)
	{
		if (!isset(RuleCollection::$rules[$alias])) {
			RuleCollection::$rules[$alias] = new Rule($alias);
		}

		RuleCollection::$rules[$alias]->shared = true;
	}

	public function define($alias, $parameters, $share = false)
	{
		$Rule = new Rule($alias);

		foreach ($parameters as $key => $value) {
			if ($key[0] === ":") {
				$Rule->setParameter($value);
			} else {
				$Rule->setDependency($key, $value);
			}
		}

		$Rule->shared = $share;
		RuleCollection::$rules[$alias] = $Rule;
	}

	public function defineMethod($callable = [], $parameters)
	{
		$bind = $callable[0];
		$method = $callable[1];

		if (is_object($bind)) {
			$class = get_class($bind);
			$Rule = $this->getRule($class, true);
			$Rule->setInstance($bind);
		} else {
			$class = $bind;
			$Rule = $this->getRule($class, true);
		}

		foreach ($parameters as $key => $value) {
			if ($key[0] === ":") {
				$Rule->setParameter(ltrim($key, ':'), $method);
			} else {
				$Rule->setDependency($key, $value, $method);
			}
		}
		RuleCollection::$rules[$alias] = $Rule;
	}

	public function prepareParameters($parameters, $arguments = [], $ruleParameters = [])
	{
		$values = [];

		foreach ($arguments as $argIndex => $argument) {
			if (is_string($argIndex)) {
				foreach ($parameters as $paramIndex => $parameter) {
					if ($argIndex == $parameter->getName()) {
						$values[$paramIndex] = $argument;
						unset($arguments[$argIndex]);
						break;
					}
				}
			}
		}

		$count = 0;
		foreach ($parameters as $paramIndex => $parameter) {

			/* Is parameter hinted with class? */
			if ($class = $parameter->getClass()) {

				$className = $class->getName();

				if (isset($ruleParameters["dependencies"][$className])) {
					$values[$paramIndex] = $ruleParameters["dependencies"][$className];
				} else {
					$values[$paramIndex] = $this->make($className);
				}

			/* Nope normal parameter */
			} else {
				if (isset($ruleParameters["parameters"][$count])) {
					$values[$paramIndex] = $ruleParameters["parameters"][$count];
					$count++;
				}
			}
		}


		return $values;
	}

	public function make($alias, $arguments = [])
	{
		/* Clean the mess */
		$alias = (string) $alias;
		$arguments = (array) $arguments;

		$Rule = RuleCollection::getRule($alias, true);

		$shared = $Rule->shared;
		$hasInstance = false;

		/* Has Instance */
		if ($shared) {
			$hasInstance = $Rule->hasInstance;
		}

		/* Get  if rule is set to shared and has an instance for alias */
		if ($shared && $hasInstance) {
			/* Return instance */
			return $Rule->Instance;
		}

		if (isset(RuleCollection::$map[$alias])) {

			$binding = RuleCollection::$map[$alias];

			if (is_callable($binding)) {
				return call_user_func_array($binding, $arguments);
			} else if (is_object($binding)) {
				return $binding;
			} else {
				$alias =	$binding;
			}
		}

		$ReflectionClass = $Rule->getReflectionClass();

		if ($Rule->reflectionable && !$Rule->hasReflectionClass) {
			$Rule->setReflectionClass($ReflectionClass);
		}

		if (!$ReflectionClass->isInstantiable()) {
			throw new InvalidArgumentException("$alias is not an instantiable class");
		}

		$Constructor = $ReflectionClass->getConstructor();

		if ($Constructor === null) {
			$Instance = $ReflectionClass->newInstance();

			if ($shared && !$hasInstance) {
				return $Rule->setInstance($Instance);
			} else {
				return $Instance;
			}
		}

		$ReflectionParameters = $Constructor->getParameters();
		$parameters = $this->prepareParameters($ReflectionParameters, $arguments, $Rule->getDefinition());

		if ($shared && !$hasInstance) {
			return RuleCollection::$rules[$alias]->setInstance($ReflectionClass->newInstanceArgs($parameters));
		} else {
			return $ReflectionClass->newInstanceArgs($parameters);
		}
	}

	public function callMethod($alias, $method = '', $arguments = [])
	{
		if (isset(RuleCollection::$rules[$alias])) {

			$Rule = RuleCollection::$rules[$alias];

			if ($Rule->getReflectionClass()->hasMethod($method)) {

				$ReflectionMethod = $Rule->ReflectionClass->getMethod($method);
				$parameters = $ReflectionMethod->getParameters();
				$values = $this->prepareParameters($parameters, $arguments, $Rule->getDefinition($method), $method);

				if ($Rule->hasInstance) {
					return $ReflectionMethod->invokeArgs($Rule->Instance, $values);
				} else {
					return $ReflectionMethod->invokeArgs($this->make($Rule->getName()), $values);
				}
			}
		}

		return false;
	}
}
