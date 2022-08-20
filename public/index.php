<?php

use Croft\Croft;
use Croft\From;

require From::VENDOR->dir() . 'autoload.php';

(function (Croft $croft) {
    $croft->pushCrops();
})(
    (require From::BOOTSTRAP->dir() . 'ioc.php')
        ->create('$aetheria')
);
