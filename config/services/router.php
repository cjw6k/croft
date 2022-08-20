<?php

use Croft\Config;
use Croft\Request;
use a6a\a6a\Router\Router as RouterA6a;
use Croft\Router;
use a6a\a6a\Config\Config as ConfigA6a;
use a6a\a6a\Request\Request as RequestA6a;

return [
    RouterA6a::class => [
        'instanceOf' => Router::class,
        'substitutions' => [
            ConfigA6a::class => Config::class,
            RequestA6a::class => Request::class,
        ],
    ],
];
