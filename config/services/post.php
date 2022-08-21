<?php

use a6a\a6a\Post\Post;
use Croft\Post as CroftPost;

return [
    Post::class => [
        'instanceOf' => CroftPost::class,
        'shared' => true,
    ],
];
