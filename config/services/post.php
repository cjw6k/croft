<?php

use Croft\Config;
use Croft\Response;
use Croft\Storage;
use a6a\a6a\Post\PostInterface;
use Croft\Post;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Storage\StorageInterface;

return [
    PostInterface::class => [
        'instanceOf' => Post::class,
        'substitutions' => [
            ConfigInterface::class => Config::class,
            ResponseInterface::class => Response::class,
            StorageInterface::class => Storage::class,
        ],
    ],
];
