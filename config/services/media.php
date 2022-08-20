<?php

use a6a\a6a\Media\MediaInterface;
use Croft\Media;
use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Storage\StorageInterface;
use Croft\Storage;

return [
    MediaInterface::class => [
        'instanceOf' => Media::class,
        'substitutions' => [
            ResponseInterface::class => ResponseInterface::class,
            StorageInterface::class => Storage::class,
        ],
    ],
];
