<?php

namespace Stub;

class B
{
   private $A;

   public function __construct(A $A)
   {
      $this->A = $A;
   }

   public function getA()
   {
      return $this->A;
   }
}
