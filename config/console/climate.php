<?php

use Composer\InstalledVersions;
use Croft\From;
use Dice\Dice;
use League\CLImate\Argument\Manager;
use League\CLImate\CLImate;

return [
    CLImate::class => [
        'shared' => true,
        'call' => [
            ['setArgumentManager', [[Dice::INSTANCE => '$CLImateArgumentManager']]],
            ['__call', ['addArt', [From::ART->dir()]]],
        ],
    ],
    '$CLImateArgumentManager' => [
        'instanceOf' => Manager::class,
        'shared' => true,
        'call' => [
            [
                'add',
                [
                    [
                        'help' => [
                            'prefix' => 'h',
                            'longPrefix' => 'help',
                            'description' => 'Display this help message.',
                        ],
                        'version' => [
                            'prefix' => 'v',
                            'longPrefix' => 'version',
                            'description' => 'Display the Croft version.',
                        ],
                        'list' => [
                            'prefix' => 'l',
                            'longPrefix' => 'list',
                            'description' => 'List available commands.',
                        ],
                        'command' => [
                            'castTo' => 'string',
                        ],
                    ],
                ],
            ],
            [
                'description',
                [
                    'croft: v' . InstalledVersions::getPrettyVersion('croft/croft')
                    . ' (PHP ' . PHP_VERSION . ' - ' . PHP_SAPI . ')',
                ],
            ],
        ],
    ],
];
