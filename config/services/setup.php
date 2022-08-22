<?php

use a6a\a6a\Setup\Setup;
use Croft\Setup as CroftSetup;

return [
    Setup::class => [
        'instanceOf' => CroftSetup::class,
        'shared' => true,
    ],
];
