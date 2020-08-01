<?php

namespace spec\cjw6k\WebFoo\MediaFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\MediaFoo\Media;

class MediaSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\ConfigFoo\Config(FIXTURES_ROOT . 'config-basic.yml');
		$storage = new \cjw6k\WebFoo\StorageFoo\Storage($config);
		$response = new \cjw6k\WebFoo\ResponseFoo\Response();
		$this->beConstructedWith($response, $storage);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Media::class);
    }
}
