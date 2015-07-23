<?php

namespace Stub;

class TypedConstructor
{
   private $A;
   private $B;

   public function __construct(A $A, B $B)
   {
      $this->A = $A;
      $this->B = $B;
   }

   public function getA()
   {
      return $this->A;
   }

   public function getB()
   {
      return $this->B;
   }
}
