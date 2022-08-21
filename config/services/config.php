<?php

use Croft\Config as CroftConfig;
use a6a\a6a\Config\Config;

return [
    Config::class => [
        'instanceOf' => CroftConfig::class,
        'shared' => true,
    ],
];
