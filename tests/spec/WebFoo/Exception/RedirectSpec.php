<?php

namespace spec\cjw6k\WebFoo\Exception;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Exception\Redirect;

class RedirectSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Redirect::class);
    }
}
