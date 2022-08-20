<?php

use a6a\a6a\Config\Config as ConfigA6a;
use a6a\a6a\Storage\Storage as StorageA6a;
use Croft\Config;
use Croft\Storage;

return [
    StorageA6a::class => [
        'instanceOf' => Storage::class,
        'substitutions' => [
            ConfigA6a::class => Config::class,
        ],
    ],
];
