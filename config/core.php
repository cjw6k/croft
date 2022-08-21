<?php

use Chapeau\ConsoleApp;
use Croft\Croft;
use Croft\IndieAuth\IndieAuth;
use Croft\Micropub\Micropub;
use Croft\Webmention\Webmention;
use Dice\Dice;
use League\Pipeline\PipelineInterface;

return [
    '$aetheria' => [
        'instanceOf' => Croft::class,
        'shared' => true,
        'call' => [
            ['extend', [[Dice::INSTANCE => IndieAuth::class]]],
            ['extend', [[Dice::INSTANCE => Micropub::class]]],
            ['extend', [[Dice::INSTANCE => Webmention::class]]],
        ],
    ],
    '$aetheriaCli' => [
        'instanceOf' => ConsoleApp::class,
        'shared' => true,
        'substitutions' => [
            PipelineInterface::class => '$cliPipeline',
        ],
    ],
];
