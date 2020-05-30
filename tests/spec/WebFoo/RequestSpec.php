<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Request;

class RequestSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Request::class);
    }
}
