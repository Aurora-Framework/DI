<?php
require __DIR__ . "./../vendor/autoload.php"; // Autoload files using Composer

$Loader = new Aurora\Di\Loader(
	(new Aurora\Adapter\JSON())->loadFile("./services")
);
$Injector = $Loader->load();

class A
{
   function __construct($b = "b")
   {
      $this->b = $b;
	}
}

var_dump($Injector->make("AClass"));
