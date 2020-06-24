<?php

namespace spec\cjw6k\WebFoo\Micropub;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub\Query;

class QuerySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Query::class);
    }
}
