<?php

use a6a\a6a\Storage\Storage;
use Croft\Storage as CroftStorage;

return [
    Storage::class => [
        'instanceOf' => CroftStorage::class,
        'shared' => true,
    ],
];
