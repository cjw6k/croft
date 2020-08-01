<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\WebFoo;

class WebFooSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Config\ConfigInterface $config,
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response,
		\cjw6k\WebFoo\Router\RouterInterface $router,
		\cjw6k\WebFoo\Session\SessionInterface $session,
		\cjw6k\WebFoo\Storage\StorageInterface $storage,
		\cjw6k\WebFoo\Async\AsyncInterface $async,
		\cjw6k\WebFoo\IndieAuth\IndieAuth $indieauth,
		\cjw6k\WebFoo\Micropub\Micropub $micropub,
		\cjw6k\WebFoo\Webmention\Webmention $webmention
	){
		$this->beConstructedWith(
			array(
				'config' => $config,
				'request' => $request,
				'response' => $response,
				'router' => $router,
				'session' => $session,
				'storage' => $storage,
				'async' => $async,
			),
			array(
				$indieauth,
				$micropub,
				$webmention,
			)
		);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(WebFoo::class);
    }

}

