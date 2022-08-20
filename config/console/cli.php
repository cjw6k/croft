<?php

// todo: check if running in console

use Croft\Console\Hub;
use Dice\Dice;
use League\Pipeline\Pipeline;

$next = static fn (string $classFqn): array => ['pipe', [[Dice::INSTANCE => $classFqn]], Dice::CHAIN_CALL];

return [
    '$cliPipeline' => [
        'instanceOf' => Pipeline::class,
        'call' => [
            $next(Hub::class),
        ],
    ],
];
