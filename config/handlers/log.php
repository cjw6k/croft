<?php

use Psr\Log\LoggerInterface;

return [
    log::class => [
    ],
];

function og(string $ogMessage): void
{
    if (empty($_ENV['DEBUG_TO_STDERR'])) {
        return;
    }

    // todo: use the logging interface
    fwrite(STDERR, $ogMessage . PHP_EOL);
}
