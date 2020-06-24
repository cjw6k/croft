<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\Validation;

class ValidationSpec extends ObjectBehavior
{
	function let()
	{
		$request = new \cjw6k\WebFoo\Request();
		$config = new \cjw6k\WebFoo\Config( FIXTURES_ROOT . 'config-basic.yml' );
		$this->beConstructedWith($request, $config);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Validation::class);
    }
}
