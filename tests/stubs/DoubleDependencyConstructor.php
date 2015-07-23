<?php

namespace Stub;

class DoubleDependencyConstructor
{
   private $A;
   private $B1;
   private $B2;

   public function __construct(A $A, B $B1, B $B2)
   {
      $this->A = $A;
      $this->B1 = $B1;
      $this->B2 = $B2;
   }

   public function getA()
   {
      return $this->A;
   }

   public function getB1()
   {
      return $this->B1;
   }

   public function getB2()
   {
      return $this->B2;
   }
}
