<?php

use Croft\Croft;
use Croft\From;

require __DIR__ . '/../vendor/autoload.php';

(static function (Croft $croft): void {
    $croft->pushCrops();
})(
    (require From::BOOTSTRAP->dir() . 'ioc.php')
        ->create('$aetheria')
);
