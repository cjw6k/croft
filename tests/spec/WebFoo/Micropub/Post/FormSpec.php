<?php

namespace spec\cjw6k\WebFoo\Micropub\Post;

use PhpSpec\ObjectBehavior;
use cjw6k\WebFoo\Micropub\Post\Form;

class FormSpec extends ObjectBehavior
{
	function let()
	{
		$config = new \cjw6k\WebFoo\Config(FIXTURES_ROOT . 'config-basic.yml');
		$post = new \cjw6k\WebFoo\Post($config);
		$this->beConstructedWith($post);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Form::class);
    }
}
