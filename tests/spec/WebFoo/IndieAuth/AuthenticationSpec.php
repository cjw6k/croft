<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\Authentication;

class AuthenticationSpec extends ObjectBehavior
{
	function let(\cjw6k\WebFoo\Request\RequestInterface $request)
	{
		$this->beConstructedWith($request);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Authentication::class);
    }
}
