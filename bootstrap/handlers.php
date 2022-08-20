<?php

use Croft\From;
use Dice\Dice;

$bootstrapper = static function (string $name, string $handlerPath): void {
    $boots = (new Dice())->addRules(include From::CONFIG->dir() . "handlers/{$handlerPath}.php");
    $boots->create($name);
};

match (PHP_SAPI) {
    'cli' => $bootstrapper('$nmCollision', 'collision'),
    default => $bootstrapper('$whoops', 'whoops'),
};
