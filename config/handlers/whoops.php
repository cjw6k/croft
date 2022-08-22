<?php

use Dice\Dice;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

return [
    '$whoops' => [
        'instanceOf' => Run::class,
        'call' => [
            ['pushHandler', [[Dice::INSTANCE => PrettyPageHandler::class]]],
            ['register'],
        ],
    ],
];
