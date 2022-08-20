<?php

use Croft\Config;
use Croft\Request;
use Dice\Dice;
use a6a\a6a\Router\RouterInterface;
use Croft\Router;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Request\RequestInterface;

return [
    RouterInterface::class => [
        'instanceOf' => Router::class,
        'substitutions' => [
            ConfigInterface::class => Config::class,
            RequestInterface::class => Request::class,
        ],
    ],
];
