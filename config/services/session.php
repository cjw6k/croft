<?php

use a6a\a6a\Session\Session as SessionA6a;
use Croft\Session;

return [
    SessionA6a::class => [
        'instanceOf' => Session::class,
    ],
];
