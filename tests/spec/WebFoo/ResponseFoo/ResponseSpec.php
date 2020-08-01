<?php

namespace spec\cjw6k\WebFoo\ResponseFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\ResponseFoo\Response;

class ResponseSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Response::class);
    }
}
