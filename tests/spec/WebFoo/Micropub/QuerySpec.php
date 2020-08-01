<?php

namespace spec\cjw6k\WebFoo\Micropub;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub\Query;

class QuerySpec extends ObjectBehavior
{
	function let(
		\cjw6k\WebFoo\Request\RequestInterface $request,
		\cjw6k\WebFoo\Response\ResponseInterface $response
	){
		$this->beConstructedWith($request, $response);
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Query::class);
    }
}
