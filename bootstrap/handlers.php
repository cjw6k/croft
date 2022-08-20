<?php

use Croft\From;
use Dice\Dice;

$bootstrapper = function(string $name, string $handlerPath) {
    $boots = (new Dice())->addRules(require From::CONFIG->dir() . "handlers/{$handlerPath}.php");
    $boots->create($name);
};

match (PHP_SAPI) {
    'cli' => $bootstrapper('$nmCollision', 'collision'),
    default => $bootstrapper('$whoops', 'whoops'),
};
