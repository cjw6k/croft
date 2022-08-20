<?php

use Croft\Config;
use Dice\Dice;
use a6a\a6a\Config\ConfigInterface;

return [
    ConfigInterface::class => [
        'instanceOf' => Config::class,
        'shared' => true,
    ],
];
