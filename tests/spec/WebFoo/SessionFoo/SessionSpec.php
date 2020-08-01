<?php

namespace spec\cjw6k\WebFoo\SessionFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\SessionFoo\Session;

class SessionSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\ConfigFoo\Config( FIXTURES_ROOT . 'config-basic.yml' );
		$request = new \cjw6k\WebFoo\RequestFoo\Request();
		$this->beConstructedWith($config, $request);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Session::class);
    }
}
