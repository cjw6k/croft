<?php

use a6a\a6a\Page\Page;
use Croft\Page as CroftPage;

return [
    Page::class => [
        'instanceOf' => CroftPage::class,
        'shared' => true,
    ],
];
