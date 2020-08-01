<?php

namespace spec\cjw6k\WebFoo\Setup;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Setup\Setup;

class SetupSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Request\RequestInterface $request
	){
		$this->beConstructedWith($request);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Setup::class);
    }
}
