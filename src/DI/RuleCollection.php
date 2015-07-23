<?php

namespace Aurora\DI;

class RuleCollection
{
   public static $rules;
   public static $maps;

   public static function addRule(Rule $Rule, $alias = null)
   {
      if ($alias === null) {
			$alias = $Rule->alias;
		}
      self::$rules[$alias] = $Rule;
   }

   public static function getRule($alias, $recuisive = false)
   {
      return (isset(self::$rules[$alias])) ? self::$rules[$alias] : (
         ($recuisive === true) ? self::$rules[$alias] = new Rule($alias) : new Rule($alias)
      );
   }

   public function define($alias, $parameters, $shared = false)
   {
      $Rule = self::getRule($alias, true);

      foreach ($parameters as $key => $value) {
         if ($key[0] === ":") {
            $key[0] = "";
            $Rule->setKeyParameter(ltrim($key), $value);
         } else {
            $Rule->setDependency($key, $value);
         }
      }

      $Rule->shared = $shared;
   }

   public function share($alias)
	{
      self::getRule($alias, true)->shared = true;
	}

   public function bind($alias)
	{
      self::getRule($alias, true)->shared = true;
	}

   public function alias($alias, $binding)
	{
		self::$maps[$alias] = $binding;
	}

   private function __construct(){}
   private function __clone(){}
}
