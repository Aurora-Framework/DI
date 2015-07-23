<?php

require './vendor/autoload.php';

use Aurora\Injector;
use Aurora\DI\RuleCollection;

class RuleDependencyInjectionTest extends PHPUnit_Framework_TestCase
{
   public function testAlias()
   {
      $Resolver = (new Injector());
      RuleCollection::bind('test', 'Stub\\NoConstructor');
      $Instance = $Resolver->make('test');

      $this->assertInstanceOf('Stub\\NoConstructor', $Instance);
   }

   public function testScalarWithStringKeyParameters()
   {
      RuleCollection::define('Stub\\ScalarConstructor', [
         ":val1" => "b",
         ":val2" => "c"
      ]);

      $Instance = (new Injector())
         ->make('Stub\\ScalarConstructor');

      $this->assertEquals('b', $Instance->getVal1());
      $this->assertEquals('c', $Instance->getVal2());
   }

   public function testAutoInjectionPartialOverrideWithBAndStringKey()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      RuleCollection::define('Stub\\TypedConstructor', [
         "B" => $B
      ]);


      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor');

      $this->assertEquals($B, $Instance->getB());
   }

   public function testAutoInjectionFullOverrideWithStringKey()
   {
      $A = (new Injector())
         ->make('Stub\\A');
      $B = (new Injector())
         ->make('Stub\\B', [$A]);

      RuleCollection::define('Stub\\TypedConstructor', [
         "A" => $A,
         "B" => $B
      ]);

      $Instance = (new Injector())
         ->make('Stub\\TypedConstructor');

      $this->assertEquals($A, $Instance->getA());
      $this->assertEquals($B, $Instance->getB());
      $this->assertEquals($A, $Instance->getB()->getA());
   }


   public function testUnorderedInterleavedWithStringKeyInjection()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      RuleCollection::define('Stub\\MixedConstructor', [
         ":val1" => 'test1',
         ":val2" => 'test2',
         "B" => $B
      ]);

      $Instance = (new Injector())
         ->make('Stub\\MixedConstructor');

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertEquals($B, $Instance->getB());
      $this->assertEquals('test1', $Instance->getVal1());
      $this->assertEquals('test2', $Instance->getVal2());
   }

   public function testMultipleInjectionsOfOneClassWithOneOverride()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      RuleCollection::define('Stub\\MixedConstructor', [
         "B" => $B
      ]);
      $Instance = (new Injector())
         ->make('Stub\\DoubleDependencyConstructor');

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB1());
      $this->assertInstanceOf('Stub\\B', $Instance->getB2());
      $this->assertNotEquals(spl_object_hash($Instance->getB1()), spl_object_hash($Instance->getB2()));
   }

   public function testMultipleInjectionsOfOneClassWithOverrides()
   {
      $B = (new Injector())
         ->make('Stub\\B');

      RuleCollection::define('Stub\\DoubleDependencyConstructor', [
         ":B1" => $B,
         ":B2" => $B
      ]);

      $Instance = (new Injector())
         ->make('Stub\\DoubleDependencyConstructor');

      $this->assertInstanceOf('Stub\\A', $Instance->getA());
      $this->assertInstanceOf('Stub\\B', $Instance->getB1());
      $this->assertInstanceOf('Stub\\B', $Instance->getB2());
      $this->assertEquals(spl_object_hash($Instance->getB1()), spl_object_hash($Instance->getB2()));
   }

   public function testKeyedInjection()
   {
      RuleCollection::define('Stub\\MixedConstructor', [
         ':val2' => 'test1',
         ':val1' => 'test2'
      ]);

      $Instance = (new Injector())
         ->make('Stub\\MixedConstructor');

      $this->assertEquals('test1', $Instance->getVal2());
      $this->assertEquals('test2', $Instance->getVal1());
   }
}
