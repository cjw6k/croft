<?php

use Croft\Croft;
use Croft\From;

require __DIR__ . '/../vendor/autoload.php';

(function (Croft $croft) {
    $croft->pushCrops();
})(
    (require From::BOOTSTRAP->dir() . 'ioc.php')
        ->create('$aetheria')
);
