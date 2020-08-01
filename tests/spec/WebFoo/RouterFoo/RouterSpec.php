<?php

namespace spec\cjw6k\WebFoo\RouterFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\RouterFoo\Router;

class RouterSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\ConfigFoo\Config( FIXTURES_ROOT . 'config-basic.yml' );
		$request = new \cjw6k\WebFoo\RequestFoo\Request();
		$this->beConstructedWith($config, $request);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Router::class);
    }
}
