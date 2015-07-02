<?php

namespace Aurora\DI;

interface ResolverInterface
{
	public function make($alias, $arguments = []);
}
