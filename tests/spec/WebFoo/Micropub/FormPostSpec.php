<?php

namespace spec\cjw6k\WebFoo\Micropub;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub\FormPost;

class FormPostSpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Post\PostInterface $post,
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response
	){
		$this->beConstructedWith($post, $request, $response);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(FormPost::class);
    }
}
