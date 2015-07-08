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

   private function __construct(){}
   private function __clone(){}
}
