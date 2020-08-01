<?php

namespace spec\cjw6k\WebFoo\Router;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Router\Route;

class RouteSpec extends ObjectBehavior
{
	
    function it_is_initializable()
    {
		$this->beConstructedWith('GET', '/', 'home');
        $this->shouldHaveType(Route::class);
    }
}
