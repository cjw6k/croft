<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub;

class MicropubSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Micropub::class);
    }
}
