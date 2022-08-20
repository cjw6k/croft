<?php

use a6a\a6a\Media\Media as MediaA6a;
use Croft\Media;
use a6a\a6a\Response\Response as ResponseA6a;
use a6a\a6a\Storage\Storage as StorageA6a;
use Croft\Response;
use Croft\Storage;

return [
    MediaA6a::class => [
        'instanceOf' => Media::class,
        'substitutions' => [
            ResponseA6a::class => Response::class,
            StorageA6a::class => Storage::class,
        ],
    ],
];
