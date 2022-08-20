<?php

use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Storage\StorageInterface;
use Croft\Config;
use Croft\Storage;

return [
    StorageInterface::class => [
        'instanceOf' => Storage::class,
        'substitutions' => [
            ConfigInterface::class => Config::class,
        ],
    ],
];
