<?php

use Croft\From;
use Dice\Dice;

$bootstraps = (new Dice())
    ->addRules(require From::CONFIG->dir() . 'handlers/collision.php');

$bootstraps->create('$nmCollision');
