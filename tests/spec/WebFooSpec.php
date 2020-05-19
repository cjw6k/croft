<?php

namespace spec\cjw6k;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo;

class WebFooSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(WebFoo::class);
    }

	function it_loads_the_configuration_on_construction()
	{
		$this->getConfig()->shouldHaveType(WebFoo\Config::class);
	}

	function it_may_be_constructed_with_a_specific_config_file()
	{
		$this->beConstructedWith( FIXTURES_ROOT . 'config-basic.yml' );
		$this->getConfig()->getTitle()->shouldNotBeNull();
		$this->getConfig()->getTitle()->shouldBe( 'WebFoo Basic' );
	}
}

