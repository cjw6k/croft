<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\IndieAuth;

class IndieAuthSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Config\ConfigInterface $config,
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response,
		\cjw6k\WebFoo\Session\SessionInterface $session,
		\cjw6k\WebFoo\Storage\StorageInterface $storage
	){

		$this->beConstructedWith($config, $request, $response, $session, $storage);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(IndieAuth::class);
    }
}
