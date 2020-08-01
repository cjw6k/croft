<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\Validation;

class ValidationSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Config\ConfigInterface $config,
		\cjw6k\WebFoo\Request\RequestInterface $request
	){
		$this->beConstructedWith($config, $request);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Validation::class);
    }
}
