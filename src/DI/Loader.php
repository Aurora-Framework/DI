<?php

namespace Aurora\DI;

use Aurora\Injector;

class Loader
{
   public $data;
   private $default = [
      "alias" => null,
      "shared" => false,
      "parameters" => [],
      "dependencies" => [],
      "definition" => [],
      "method" => []
   ];

   public function __construct(array $data)
   {
      $this->data = $data;
   }

   public function load()
   {
      $Injector = new Injector();

      foreach ($this->data as $alias => $data) {
         $data = array_merge($this->default, $data);

         if ($data["alias"] !== null) {
            RuleCollection::alias($data["alias"], $alias);
         }

         $Rule = RuleCollection::getRule($alias, true);
         $Rule->shared = $data["shared"];
         $Rule->setParametersArray($data["parameters"]);
         $Rule->setDependenciesArray($data["dependencies"]);
      }

      return $Injector;
   }
}
