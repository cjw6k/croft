<?php

use Chapeau\ConsoleApp;
use Croft\Croft;
use League\Pipeline\PipelineInterface;

return [
    '$aetheria' => [
        'instanceOf' => Croft::class,
    ],
    '$aetheriaCli' => [
        'instanceOf' => ConsoleApp::class,
        'shared' => true,
        'substitutions' => [
            PipelineInterface::class => '$cliPipeline',
        ],
    ],
];
