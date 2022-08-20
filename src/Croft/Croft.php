<?php

namespace Croft;

use a6a\a6a\Aetheria;
use a6a\a6a\Async\Async;
use a6a\a6a\Config\Config;
use a6a\a6a\Media\Media;
use a6a\a6a\Page\Page;
use a6a\a6a\Post\Post;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Router;
use a6a\a6a\Session\Session;
use a6a\a6a\Setup\Setup;
use a6a\a6a\Storage\Storage;
use A6A\Aether\Aether;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Extension\Extension;
use a6a\a6a\Response\HttpLinkable;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Service\Service;
use a6a\a6a\Setup\Setupable;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\Store;
use League\CLImate\CLImate;

class Croft extends Aetheria
{
    public function pushCrops(): void
    {
        $this->some();
    }
}
