<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\Authorization;

class AuthorizationSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Config\ConfigInterface $config,
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response
	){
		$this->beConstructedWith($config, $request, $response);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Authorization::class);
    }
}
