<?php

use Croft\From;
use Dice\Dice;

$bootstraps = (new Dice())
    ->addRules(require From::CONFIG->dir() . 'handlers/collision.php')
    // log isn't ready
    ->addRules(require From::CONFIG->dir() . 'handlers/log.php');

$bootstraps->create('$nmCollision');
