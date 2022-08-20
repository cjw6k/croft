<?php

use Croft\From;

function make(string $classString) {
    static $context;
    if (! $context) {
        $context = require From::BOOTSTRAP->dir() . 'ioc.php';
    }

    return $context->create($classString);
}
