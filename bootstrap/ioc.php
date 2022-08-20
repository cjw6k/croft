<?php

use Croft\From;
use Dice\Dice;

$service = static fn (string $configFile) => require From::CONFIG->dir() . "services/{$configFile}.php";

return (new Dice())
    ->addRules(
        array_merge(
            require From::CONFIG->dir() . 'console/climate.php',
            require From::CONFIG->dir() . 'console/cli.php',
            require From::CONFIG->dir() . 'core.php',
            $service('async'),
            $service('config'),
            $service('media'),
            $service('page'),
            $service('post'),
            $service('request'),
            $service('response'),
            $service('router'),
            $service('session'),
            $service('setup'),
            $service('storage')
        )
    );
