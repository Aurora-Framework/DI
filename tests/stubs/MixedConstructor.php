<?php

namespace Stub;

class MixedConstructor
{
   private $A;
   private $val1;

   private $B;
   private $val2;

   public function __construct(A $A, $val1, B $B, $val2)
   {
      $this->A = $A;
      $this->val1 = $val1;

      $this->B = $B;
      $this->val2 = $val2;
   }

   public function getA()
   {
      return $this->A;
   }

   public function getVal1()
   {
      return $this->val1;
   }

   public function getB()
   {
      return $this->B;
   }

   public function getVal2()
   {
      return $this->val2;
   }
}
