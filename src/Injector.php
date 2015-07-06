<?php

namespace Aurora;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use Aurora\DI\ResolverInterface;
use Aurora\DI\Rule;

class Injector implements ResolverInterface
{

	private $map = [];

	private $resolvers = [];

	protected $rules = [];

	public function saveReflection($alias)
	{
		$this->getRule($alias, true)->reflectionable = true;
	}

	public function bind($alias, $binding)
	{
		$this->map[$alias] = $binding;
	}

	public function alias($alias, $binding)
	{
		$this->map[$alias] = $binding;
	}

	public function share($alias)
	{
		if (isset($this->rules[$alias])) {
			$Rule = $this->rules[$alias];
		} else {
			$Rule = new Rule($alias);
		}
		$Rule->setShared();
	}

	public function define($alias, $parameters)
	{
		$Rule = new Rule($alias);

		foreach ($parameters as $key => $value) {
			if ($key[0] === ":") {
				$Rule->setParameter($value);
			} else {
				$Rule->setDependency($key, $value);
			}
		}
		$this->rules[$alias] = $Rule;
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
		$this->rules[$class] = $Rule;
	}

	public function addResolver(ResolverInterface $resolver)
	{
		$this->resolvers[] = $resolver;
	}

	public function prepareParameters($parameters, $arguments = [], $ruleParameters = [])
	{
		$values = [];

		/* Prepare all parameters that aren't set to null */
		for ($i = 0; $i < count($parameters); $i++) {
			$values[] = null;
		}

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

				/* Try to find it in arguments */
				foreach ($arguments as $argIndex => $argument) {
					if (is_object($argument)) {
						if ($class->isInstance($argument)) {
							$values[$paramIndex] = $argument;
							unset($arguments[$argIndex]);
							break;
						}
					}
				}

			/* Nope normal parameter */
			} else {
				if (isset($ruleParameters["parameters"][$count])) {
					$values[$paramIndex] = $ruleParameters["parameters"][$count];
					$count++;
				}
			}
		}

		foreach ($parameters as $paramIndex => $parameter) {

			if (!isset($values[$paramIndex])) {

				if ($class = $parameter->getClass()) {

					if (isset($ruleParameters["dependencies"][$class->getName()])) {
						$values[$paramIndex] = $ruleParameters["dependencies"][$class->getName()];
					} else {
						$values[$paramIndex] = $this->make($class->getName());
					}

				} else {
					$values[$paramIndex] = array_shift($arguments);
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

		$Rule = $this->getRule($alias);

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

		foreach ($this->resolvers as $resolver) {

			$binding = $resolver->make($alias, $arguments);

			if ($binding !== null) {
				return $binding;
			}
		}

		if (isset($this->map[$alias])) {

			$binding = $this->map[$alias];

			if (is_callable($binding)) {

				return call_user_func_array($binding, $arguments);

			} elseif (is_object($binding)) {
				return $binding;
			}
		} else {
			$binding = $alias;
		}

		$ReflectionClass = $this->getReflectionClass($binding);

		if ($Rule->reflectionable && !$Rule->hasReflectionClass()) {
			$Rule->setReflectionClass($ReflectionClass);
		}

		if (!$ReflectionClass->isInstantiable()) {
			throw new InvalidArgumentException("$binding is not an instantiable class");
		}

		$Constructor = $ReflectionClass->getConstructor();

		if (empty($Constructor)) {
			if ($shared && !$hasInstance) {
				return $Rule->setInstance($ReflectionClass->newInstanceArgs($values));
			} else {
				return $ReflectionClass->newInstance();
			}
		}

		$ReflectionParameters = $Constructor->getParameters();
		$values = $this->prepareParameters($ReflectionParameters, $arguments, $Rule->getParameters());

		if ($shared && !$hasInstance) {
			return $this->rules[$alias]->setInstance($ReflectionClass->newInstanceArgs($values));
		} else {
			return $ReflectionClass->newInstanceArgs($values);
		}
	}

	public function addRule(Rule $Rule, $alias = null)
	{
		if ($alias === null) {
			$alias = $Rule->alias;
		}
		return $this->rules[$alias] = $Rule;
	}

	public function getReflectionClass($alias)
	{
		return $this->getRule($alias)->getReflectionClass();
	}

	public function getRule($alias, $recuisive = false)
	{
		return (isset($this->rules[$alias])) ? $this->rules[$alias] : (($recuisive === true) ? $this->rules[$alias] = new Rule($alias) : new Rule($alias));
	}

	public function callMethod($alias, $method = '', $arguments = [])
	{
		if (isset($this->rules[$alias])) {

			$Rule = $this->rules[$alias];

			if ($Rule->getReflectionClass()->hasMethod($method)) {

				$ReflectionMethod = $Rule->ReflectionClass->getMethod($method);
				$parameters = $ReflectionMethod->getParameters();
				$values = $this->prepareParameters($parameters, $arguments, $Rule->getParameters($method));

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
