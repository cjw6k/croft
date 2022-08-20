<?php

use Croft\Config;
use a6a\a6a\Config\Config as ConfigA6a;

return [
    ConfigA6a::class => [
        'instanceOf' => Config::class,
        'shared' => true,
    ],
];
