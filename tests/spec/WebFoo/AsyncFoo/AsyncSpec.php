<?php

namespace spec\cjw6k\WebFoo\AsyncFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\AsyncFoo\Async;

class AsyncSpec extends ObjectBehavior
{
	function let(\cjw6k\WebFoo\StorageFoo\Storage $storage)
	{
		$this->beConstructedWith($storage);		
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Async::class);
    }
}
