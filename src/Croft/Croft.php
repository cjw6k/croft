<?php

/**
 * The WebFoo class is herein defined.
 *
 * @link https://cj.w6k.ca/
 */

namespace Croft;

use a6a\a6a\Aetheria;
use a6a\a6a\Async\AsyncInterface;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Media\MediaInterface;
use a6a\a6a\Page\PageInterface;
use a6a\a6a\Post\PostInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Router\RouterInterface;
use a6a\a6a\Session\SessionInterface;
use a6a\a6a\Setup\SetupInterface;
use a6a\a6a\Storage\StorageInterface;
use A6A\Aether\Aether;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Extension\ExtensionInterface;
use a6a\a6a\Response\HTTPLinkable;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Service\ServiceInterface;
use a6a\a6a\Setup\Setupable;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\Store;
use League\CLImate\CLImate;

/**
 * The WebFoo Class is the main slingin' thinger.
 */
class Croft extends Aetheria
{
}
