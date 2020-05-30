<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Session;

class SessionSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\Config( FIXTURES_ROOT . 'config-basic.yml' );
		$this->beConstructedWith($config);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Session::class);
    }
}