<?php

use a6a\a6a\Router\Router;
use Croft\Router as CroftRouter;

return [
    Router::class => [
        'instanceOf' => CroftRouter::class,
        'shared' => true,
    ],
];
