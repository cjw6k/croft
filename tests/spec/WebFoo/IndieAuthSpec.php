<?php

namespace spec\cjw6k\WebFoo;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\IndieAuth;

class IndieAuthSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(IndieAuth::class);
    }
}
