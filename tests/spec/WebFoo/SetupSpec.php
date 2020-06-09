<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Setup;

class SetupSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\Config(FIXTURES_ROOT . 'config-basic.yml');
		$indieauth = new \cjw6k\WebFoo\IndieAuth($config);
		$request = new \cjw6k\WebFoo\Request();
		$this->beConstructedWith($indieauth, $request);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Setup::class);
    }
}
