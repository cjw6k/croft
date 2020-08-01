<?php

namespace spec\cjw6k\WebFoo\ConfigFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\ConfigFoo\Config;

class ConfigSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Config::class);
    }
	
	function it_may_be_constructed_with_a_specific_config_file()
	{
		$this->beConstructedWith( FIXTURES_ROOT . 'config-basic.yml' );
		$this->getTitle()->shouldNotBeNull();
		$this->getTitle()->shouldBe( 'WebFoo Basic' );
	}

	function it_uses_a_default_configuration_if_the_default_config_file_does_not_exist()
	{
		$this->getTitle()->shouldNotBeNull();
		$this->getTitle()->shouldBe( 'WebFoo' );
	}
}
