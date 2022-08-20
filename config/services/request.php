<?php

use a6a\a6a\Request\Request as RequestA6a;
use Croft\Request;

return [
    RequestA6a::class => [
        'instanceOf' => Request::class,
    ],
];
