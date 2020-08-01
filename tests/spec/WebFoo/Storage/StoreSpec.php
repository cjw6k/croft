<?php

namespace spec\cjw6k\WebFoo\Storage;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Storage\Store;

class StoreSpec extends ObjectBehavior
{
	function let()
	{
		$this->beConstructedWith(\cjw6k\WebFoo\Storage\Segment::TEMP, 'prefix', function(){}, function(){});
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Store::class);
    }
}
