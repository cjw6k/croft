<?php

namespace spec\cjw6k\WebFoo\PageFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\PageFoo\Page;

class PageSpec extends ObjectBehavior
{
	function let()
	{
		$response = new \cjw6k\WebFoo\ResponseFoo\Response();
		$config = new \cjw6k\WebFoo\ConfigFoo\Config(FIXTURES_ROOT . 'config-basic.yml');
		$storage = new \cjw6k\WebFoo\StorageFoo\Storage($config);
		$this->beConstructedWith($response, $storage);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Page::class);
    }
}
