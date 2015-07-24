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
		RuleCollection::$maps[$alias] = $binding;
	}

	public function alias($alias, $binding)
	{
		RuleCollection::$maps[$alias] = $binding;
	}

	public function share($alias)
	{
      RuleCollection::getRule($alias, true)->shared = true;
	}

	public static function getRule($alias, $recuisive = false)
   {
		RuleCollection::getRule($alias, $recuisive);
   }

	public static function addRule(Rule $Rule, $alias = null)
   {
      RuleCollection::addRule($Rule, $alias);
   }

	public function define($alias, $parameters, $share = false)
	{
		RuleCollection::define($alias, $parameters, $share);
	}

	public function defineMethod($callable = [], $parameters)
	{
		$alias = $callable[0];
		$method = $callable[1];

		if (is_object($bind)) {
			$alias = get_class($alias);
			$Rule = RuleCollection::getRule($alias, true)
				->Instance = $bind;
		} else {
			$Rule = $this->getRule($alias, true);
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

	public function prepare($callback)
	{
		RuleCollection::getRule($alias, true)->prepare[] = $callback;
	}

	public function prepareParameters($parameters, $arguments = [], $ruleParameters = [])
	{
		$values = [];
		$skip = [];
		$count = 0;

		foreach ($parameters as $paramIndex => $Parameter) {
			$paramName = $Parameter->getName();
			foreach ($arguments as $argIndex => $argument) {
				if ($argIndex === $paramName) {
					$values[$paramIndex] = $argument;

					$skip[$paramIndex] = $paramIndex;
					//unset($arguments[$argIndex]);
					break;
				}
			}

			if (isset($skip[$paramIndex])) continue;

			/* Is parameter hinted with class? */
			if ($class = $Parameter->getClass()) {

				$className = $class->getName();

				if (isset($ruleParameters["dependencies"][$paramName])) {
					$values[$paramIndex] = $ruleParameters["dependencies"][$paramName];
					$count++;
				} else if (isset($ruleParameters["parameters"][$paramName])) {
					$values[$paramIndex] = $ruleParameters["parameters"][$paramName];
				} else {
					$values[$paramIndex] = $this->make($className);
				}

			} else {
				if (isset($ruleParameters["parameters"][$count])) {
					$values[$paramIndex] = $ruleParameters["parameters"][$count];
					$count++;
				} else if (
					$type1 = isset($ruleParameters["parameters"][$paramName])
					|| $type2 = isset($ruleParameters["dependencies"][$paramName])
				) {
					if ($type1) {
						$values[$paramIndex] = $ruleParameters["parameters"][$paramName];
					} else {
						$values[$paramIndex] = $ruleParameters["dependenciess"][$paramName];
					}

					$count++;
				}
			}
		}

		ksort($values);

		return $values;
	}

	public function make($alias, $arguments = [])
	{
		/* Clean the mess */
		$alias = (string) $alias;
		$arguments = (array) $arguments;

		if (isset(RuleCollection::$maps[$alias])) {

			$binding = RuleCollection::$maps[$alias];

			if (is_callable($binding)) {
				$alias = call_user_func_array($binding, $arguments);
			} else {
				$alias =	$binding;
			}
		}

		$Rule = RuleCollection::getRule($alias, true);

		$shared = $Rule->shared;
		$hasInstance = false;

		/* Has Instance */
		if ($shared) {
			$hasInstance = $Rule->hasInstance;
		}

		/* Get  if rule is set to shared and has an instance for alias */
		if ($shared && $hasInstance) {
			return $this->prepareClass($Rule->Instance, $Rule);
		}

		$ReflectionClass = $Rule->getReflectionClass();

		if (!$ReflectionClass->isInstantiable()) {
			throw new InvalidArgumentException("${alias} is not an instantiable class");
		}

		$Constructor = $ReflectionClass->getConstructor();

		if ($Constructor === null) {
			$Instance = $ReflectionClass->newInstance();

			if ($shared && !$hasInstance) {
				$Rule->setInstance($Instance);
			}

			return $this->prepareClass($Instance, $Rule);
		}

		$ReflectionParameters = $Constructor->getParameters();
		$parameters = $this->prepareParameters($ReflectionParameters, $arguments, $Rule->getDefinition());
		$Instance = $ReflectionClass->newInstanceArgs($parameters);

		if ($shared && !$hasInstance) {
			RuleCollection::$rules[$alias]->Instance = $Instance;
		}

		return $this->prepareClass($Instance, $Rule);
	}

	public function prepareClass($Instance, $Rule)
	{
		foreach ($Rule->prepare as $callable) {
			$callable(...$Instance);
		}

		return $Instance;
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
