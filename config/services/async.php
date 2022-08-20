<?php

use a6a\a6a\Async\AsyncInterface;
use Croft\Async;
use a6a\a6a\Storage\StorageInterface;
use Croft\Storage;

return [
    AsyncInterface::class => [
        'instanceOf' => Async::class,
        'substitutions' => [
            StorageInterface::class => Storage::class,
        ],
    ],
];
