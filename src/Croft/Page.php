<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Page\Page as PageA6a;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Storage\Storage;

use function realpath;
use function strpos;
use function file_exists;

/**
 * The Page class slings page templates
 */
class Page implements PageA6a, Routable
{
    use Aether;

    /**
     * Store a local reference to the response
     *
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Response $response, Storage $storage)
    {
        $this->setResponse($response);
        $this->setStorage($storage);
    }

    /**
     * Provides a list of routes to register with the Router to be serviced by this service.
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed
    {
        return [
            new Route('GET', '/', 'home'),
            new Route('GET', '/{page}/{category}[/]', 'sling', ['use_vars' => true], null, 0),
            new Route('GET', '/{page}[/]', 'sling', ['use_vars' => true], null, 0),
        ];
    }

    /**
     * Control requests to the homepage
     *
     * @return array<string> The template to render, with alternate.
     */
    public function home(): array
    {
        return ['home.php', 'default.php'];
    }

    /**
     * Control page template requests
     *
     * @param array<string> $vars The hash of path components in the page request.
     *
     * @return array<string> The template to render, with alternate.
     */
    public function sling(array $vars): array
    {
        /**
         * The Router will not start this action unless page is set
         *
         * @psalm-suppress PossiblyUndefinedStringArrayOffset
         */
        $page = $vars['page'];
        $category = $vars['category'] ?? null;

        $this->setCategory($category);

        $template = realpath(From::TEMPLATES___LOCAL->dir() . 'pages/' . $page . '.php');

        if (strpos($template, realpath(From::TEMPLATES___LOCAL->dir())) !== 0) {
            $this->getResponse()->setCode(404);

            return ['404.php', 'default.php'];
        }

        if (! file_exists($template)) {
            $this->getResponse()->setCode(404);

            return ['404.php', 'default.php'];
        }

        return ['pages/' . $page . '.php', 'default.php'];
    }
}
