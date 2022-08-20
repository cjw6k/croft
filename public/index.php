<?php

use Croft\Croft;
use Croft\From;

require __DIR__ . '/../vendor/autoload.php';

/** @psalm-suppress UnresolvableInclude */
(static function (Croft $croft): void {
    $croft->pushCrops();
})(
    (require From::BOOTSTRAP->dir() . 'ioc.php')
        ->create('$aetheria')
);
