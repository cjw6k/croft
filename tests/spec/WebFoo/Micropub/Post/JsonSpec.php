<?php

namespace spec\cjw6k\WebFoo\Micropub\Post;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub\Post\Json;

class JsonSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Json::class);
    }
}
