<?php

namespace spec\cjw6k\WebFoo\IndieAuth;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth\URL;

class URLSpec extends ObjectBehavior
{
	function let(\cjw6k\WebFoo\Config\ConfigInterface $config)
	{
		$this->beConstructedWith($config);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(URL::class);
    }
}
