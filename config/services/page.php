<?php

use a6a\a6a\Page\PageInterface;
use Croft\Page;
use a6a\a6a\Response\ResponseInterface;
use Croft\Response;
use a6a\a6a\Storage\StorageInterface;
use Croft\Storage;

return [
    PageInterface::class => [
        'instanceOf' => Page::class,
        'substitutions' => [
            ResponseInterface::class => Response::class,
            StorageInterface::class => Storage::class,
        ],
    ],
];
