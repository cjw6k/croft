<?php

use a6a\a6a\Session\SessionInterface;
use Croft\Session;

return [
    SessionInterface::class => [
        'instanceOf' => Session::class,
    ],
];
