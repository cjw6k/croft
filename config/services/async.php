<?php

use a6a\a6a\Async\Async as AsyncA6a;
use Croft\Async;
use a6a\a6a\Storage\Storage as StorageA6a;
use Croft\Storage;

return [
    AsyncA6a::class => [
        'instanceOf' => Async::class,
        'substitutions' => [
            StorageA6a::class => Storage::class,
        ],
    ],
];
