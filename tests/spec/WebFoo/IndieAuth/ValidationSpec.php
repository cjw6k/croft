<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\Validation;

class ValidationSpec extends ObjectBehavior
{
	function let()
	{
		$request = new \cjw6k\WebFoo\Request();
		$this->beConstructedWith($request);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Validation::class);
    }
}
