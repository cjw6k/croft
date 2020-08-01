<?php

namespace spec\cjw6k\WebFoo\StorageFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\StorageFoo\Storage;

class StorageSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\ConfigFoo\Config( FIXTURES_ROOT . 'config-basic.yml' );
		$this->beConstructedWith($config);		
	}
	
    function it_is_initializable()
    {
        $this->shouldHaveType(Storage::class);
    }
}
