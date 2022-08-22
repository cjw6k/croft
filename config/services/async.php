<?php

use a6a\a6a\Async\Async;
use Croft\Async as CroftAsync;

return [
    Async::class => [
        'instanceOf' => CroftAsync::class,
        'shared' => true,
    ],
];
