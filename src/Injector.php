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
		RuleCollection::defineMethod($callable, $parameters);
	}

	public function prepare($alias, $callback)
	{
		RuleCollection::prepare($alias, $callback);
	}

	public function prepareParameters($parameters, $arguments = [], $ruleParameters = [])
	{
		$values = [];
		$skip = [];
		foreach ($parameters as $paramIndex => $Parameter) {
			$paramName = $Parameter->getName();
			foreach ($arguments as $argIndex => $argument) {
				if ($argIndex === $paramName) {
					$values[$paramIndex] = $argument;

					$skip[$paramIndex] = $paramIndex;
					break;
				}
			}

			if (isset($skip[$paramIndex])) continue;

			/* Is parameter hinted with class? */
			if ($class = $Parameter->getClass()) {

				$className = $class->getName();

				if (isset($ruleParameters["dependencies"][$paramName])) {
					$values[$paramIndex] = $ruleParameters["dependencies"][$paramName];
				} else if (isset($ruleParameters["parameters"][$paramName])) {
					$values[$paramIndex] = $ruleParameters["parameters"][$paramName];
				} else {
					$values[$paramIndex] = $this->make($className);
				}

			} else {
				if (
					$type1 = isset($ruleParameters["parameters"][$paramName])
					|| $type2 = isset($ruleParameters["dependencies"][$paramName])
				) {
					if ($type1) {
						$values[$paramIndex] = $ruleParameters["parameters"][$paramName];
					} else {
						$values[$paramIndex] = $ruleParameters["dependenciess"][$paramName];
					}
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

		if (isset(RuleCollection::$maps[$alias])) {

			$binding = RuleCollection::$maps[$alias];

			if (is_callable($binding)) {
				$alias = call_user_func_array($binding, $arguments);
			} else {
				$alias =	$binding;
			}
		}

		$Rule = RuleCollection::getRule($alias, true);
		$this->findParents($Rule);

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
			$Instance = $this->prepareClass($ReflectionClass->newInstance(), $Rule);

			if ($shared && !$hasInstance) {
				$Rule->setInstance($Instance);
			}

			return $this->prepareClass($Instance, $Rule);
		}

		$ReflectionParameters = $Constructor->getParameters();
		$parameters = $this->prepareParameters($ReflectionParameters, $arguments, $Rule->getDefinition());
		$Instance = $this->prepareClass($ReflectionClass->newInstanceArgs($parameters), $Rule);

		if ($shared && !$hasInstance) {
			RuleCollection::$rules[$alias]->Instance = $Instance;
		}

		return $Instance;
	}

	public function prepareClass($Instance, $Rule)
	{
		foreach ($Rule->prepare as $callable) {
			$callable($Instance);
		}
		foreach ($Rule->parents as $alias) {
			$Rule = RuleCollection::getRule($alias, true);
			foreach ($Rule->prepare as $callable) {
				$callable($Instance);
			}
		}

		return $Instance;
	}

	public function findParents($Rule)
	{
		$parents = [];

		if ($Rule->parents === null) {
			$ReflectionClass = $Rule->getReflectionClass();

			while ($Parent = $ReflectionClass->getParentClass()) {
				$parents[] = $Parent->getName();
				$ReflectionClass = $Parent;
			}
		}

		$Rule->parents = $parents;
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
