<?php

use a6a\a6a\Response\Response;
use Croft\Response as CroftResponse;

return [
    Response::class => [
        'instanceOf' => CroftResponse::class,
        'shared' => true,
    ],
];
