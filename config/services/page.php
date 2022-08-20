<?php

use a6a\a6a\Page\Page as PageA6a;
use Croft\Page;
use a6a\a6a\Response\Response as ResponseA6a;
use Croft\Response;
use a6a\a6a\Storage\Storage as StorageA6a;
use Croft\Storage;

return [
    PageA6a::class => [
        'instanceOf' => Page::class,
        'substitutions' => [
            ResponseA6a::class => Response::class,
            StorageA6a::class => Storage::class,
        ],
    ],
];
