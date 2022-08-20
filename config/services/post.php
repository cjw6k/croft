<?php

use Croft\Config;
use Croft\Response;
use Croft\Storage;
use a6a\a6a\Post\Post as PostA6a;
use Croft\Post;
use a6a\a6a\Config\Config as ConfigA6a;
use a6a\a6a\Response\Response as ResponseA6a;
use a6a\a6a\Storage\Storage as StorageA6a;

return [
    PostA6a::class => [
        'instanceOf' => Post::class,
        'substitutions' => [
            ConfigA6a::class => Config::class,
            ResponseA6a::class => Response::class,
            StorageA6a::class => Storage::class,
        ],
    ],
];
