<?php

use a6a\a6a\Session\Session;
use Croft\Session as CroftSession;

return [
    Session::class => [
        'instanceOf' => CroftSession::class,
        'shared' => true,
    ],
];
