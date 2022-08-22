<?php

use a6a\a6a\Media\Media;
use Croft\Media as CroftMedia;
use a6a\a6a\Response\Response;
use a6a\a6a\Storage\Storage;
use Dice\Dice;

return [
    Media::class => [
        'instanceOf' => CroftMedia::class,
        'shared' => true,
    ],
];
