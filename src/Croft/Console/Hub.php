<?php

namespace Croft\Console;

use a6a\a6a\Setup\Setup;
use Croft\Croft;
use Croft\From;
use League\CLImate\CLImate;
use League\Pipeline\StageInterface;

use function file_exists;
use function gettype;

class Hub implements StageInterface
{
    public const CONTINUE = 1000;

    public function __construct(private CLImate $cli, private Setup $setup, private Croft $croft)
    {
    }

    public function __invoke(mixed $payload = []): void
    {
        if (! file_exists(From::___->dir() . 'config.yml')) {
            $this->cli->info()->animation('croft')->speed(250)->run();

            if (! $this->cli->lightBlue()->confirm('Start setup?')->confirmed()) {
                $this->cli->error('Croft must be setup before it may be used.');

                return;
            }

            $this->croft->setup($this->cli, $this->setup);
        }

        $this->cli->out(gettype($payload));
    }
}
