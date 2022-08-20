<?php

use a6a\a6a\Request\RequestInterface;
use Croft\Request;

return [
    RequestInterface::class => [
        'instanceOf' => Request::class,
    ],
];
