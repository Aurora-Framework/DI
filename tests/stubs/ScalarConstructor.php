<?php

namespace Stub;

class ScalarConstructor
{
   private $val1;
   private $val2;

   public function __construct($val1, $val2)
   {
      $this->val1 = $val1;
      $this->val2 = $val2;
   }

   public function getVal1()
   {
      return $this->val1;
   }

   public function getVal2()
   {
      return $this->val2;
   }
}
