<?php

namespace spec\cjw6k;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo;

class WebFooSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(WebFoo::class);
    }
}

