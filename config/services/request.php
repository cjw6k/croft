<?php

use a6a\a6a\Request\Request;
use Croft\Request as CroftRequest;

return [
    Request::class => [
        'instanceOf' => CroftRequest::class,
        'shared' => true,
    ],
];
