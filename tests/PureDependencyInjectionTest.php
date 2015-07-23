<?php

require './vendor/autoload.php';

use Aurora\Injector;

class PureDependencyInjectionTest extends PHPUnit_Framework_TestCase
{

   public function testNoConstructor()
   {
      $Instance = (new Injector())
         ->make('Stub\\NoConstructor');

      $this->assertInstanceOf('Stub\\NoConstructor', $Instance);
   }

   public function testAlias()
   {
      $Resolver = (new Injector());
      $Resolver
         ->bind('test', 'Stub\\NoConstructor');
      $Instance = $Resolver
         ->make('test');

      $this->assertInstanceOf('Stub\\NoConstructor', $Instance);
   }

   public function testUninstantiable()
   {
      $this->setExpectedException('InvalidArgumentException');

      $Instance = (new Injector())
         ->make('Stub\\Uninstantiable');
   }

   public function testScalarWithStringKeyParameters()
   {
      $Instance = (new Injector())
         ->make('Stub\\ScalarConstructor', [
            "val1" => "b",
            "val2" => "c"
         ]);

      $this->assertEquals('b', $Instance->getVal1());
      $this->assertEquals('c', $Instance->getVal2());
   }

   public function testAutoInjection()
   {
      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor');

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB());
      $this->assertInstanceOf('Stub\\A', $Instance->getB()->getA());
   }

   public function testAutoInjectionPartialOverrideWithA()
   {
      $A = (new Injector())
         ->make('Stub\\A');
      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor', [$A]);

      $this->assertEquals($A, $Instance->getA());
   }

   public function testAutoInjectionPartialOverrideWithBAndStringKey()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor', [
            "B" => $B
         ]);

      $this->assertEquals($B, $Instance->getB());
   }

   public function testAutoInjectionFullOverrideWithStringKey()
   {
      $A = (new Injector())
         ->make('Stub\\A');
      $B = (new Injector())
         ->make('Stub\\B', [$A]);

      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor', [
            "A" => $A,
            "B" => $B
         ]);

      $this->assertEquals($A, $Instance->getA());
      $this->assertEquals($B, $Instance->getB());
      $this->assertEquals($A, $Instance->getB()->getA());
   }

   public function testUnorderedInterleavedWithStringKeyInjection()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      $Instance = (new Injector())
         ->make('Stub\\MixedConstructor', [
            "val1" => 'test1',
            "val2" => 'test2',
            "B" => $B
         ]);

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertEquals($B, $Instance->getB());
      $this->assertEquals('test1', $Instance->getVal1());
      $this->assertEquals('test2', $Instance->getVal2());
   }

   public function testMultipleInjectionsOfOneClass()
   {
      $Instance = (new Injector())
         ->make('Stub\\DoubleDependencyConstructor');

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB1());
      $this->assertInstanceOf('Stub\\B', $Instance->getB2());

      $this->assertNotEquals(spl_object_hash($Instance->getB1()), spl_object_hash($Instance->getB2()));
   }

   public function testMultipleInjectionsOfOneClassWithOneOverride()
   {
      $B = (new Injector())
         ->make('Stub\\B');
      $Instance = (new Injector())
         ->make('Stub\\DoubleDependencyConstructor', [
         "B" => $B
      ]);

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB1());
      $this->assertInstanceOf('Stub\\B', $Instance->getB2());
      $this->assertNotEquals(spl_object_hash($Instance->getB1()), spl_object_hash($Instance->getB2()));
   }

   public function testMultipleInjectionsOfOneClassWithOverrides()
   {
      $B = (new Injector())
         ->make('Stub\\B');
      $Instance = (new Injector())
         ->make('Stub\\DoubleDependencyConstructor', [
         "B1" => $B,
         "B2" => $B,
      ]);


      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB1());
      $this->assertInstanceOf('Stub\\B', $Instance->getB2());
      $this->assertEquals(spl_object_hash($Instance->getB1()), spl_object_hash($Instance->getB2()));
   }

   public function testKeyedInjection()
   {
      $Instance = (new Injector())
         ->make('Stub\\MixedConstructor', [
            'val2' => 'test1',
            'val1' => 'test2'
         ]);

      $this->assertEquals('test1', $Instance->getVal2());
      $this->assertEquals('test2', $Instance->getVal1());
   }
}
