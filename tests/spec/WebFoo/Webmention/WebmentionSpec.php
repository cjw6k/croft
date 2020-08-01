<?php

namespace spec\cjw6k\WebFoo\Webmention;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Webmention\Webmention;

class WebmentionSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Config\ConfigInterface $config,
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response,
		\cjw6k\WebFoo\Storage\StorageInterface $storage
	){
		$this->beConstructedWith($config, $request, $response, $storage);
	}	
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Webmention::class);
    }
}
