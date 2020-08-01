<?php

namespace spec\cjw6k\WebFoo\PostFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\PostFoo\Post;

class PostSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\ConfigFoo\Config(FIXTURES_ROOT . 'config-basic.yml');
		$response = new \cjw6k\WebFoo\ResponseFoo\Response();
		$storage = new \cjw6k\WebFoo\StorageFoo\Storage($config);
		$this->beConstructedWith($config, $response, $storage);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Post::class);
    }
}
