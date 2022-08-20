<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Router\Route;
use a6a\a6a\Router\RouterInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function usort;
use function FastRoute\cachedDispatcher;
use function array_merge;

/**
 * The Router service determines which object method will handle incoming request
 */
class Router implements RouterInterface
{
    use Aether;

    /**
     * Store a local reference to the active configuration and current request
     *
     * @param ConfigInterface $config The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request)
    {
        $this->setConfig($config);
        $this->setRequest($request);
    }

    /**
     * Map a request to a specific class and method, with path parameters
     *
     * @return mixed The class, method and parameters that are matched to the request.
     */
    public function route(): mixed
    {
        $routes = $this->getRoutes();
        $route_collector = static function (RouteCollector $route_collector) use ($routes): void {
            if (empty($routes)) {
                return;
            }

            $safe_routes = [];

            foreach ($routes as $route) {
                if (! ($route instanceof Route)) {
                    // throw exception
                    continue;
                }

                $safe_routes[] = $route;
            }

            if (empty($safe_routes)) {
                 return;
            }

            usort(
                $safe_routes,
                static function (Route $route_a, Route $route_b) {
                    if ($route_a->getPriority() == $route_b->getPriority()) {
                        return 0;
                    }

                    return ($route_a->getPriority() > $route_b->getPriority())
                        ? -1
                        : 1;
                }
            );

            foreach ($safe_routes as $route) {
                [$verbs, $regex, $data] = $route->pack();
                $route_collector->addRoute($verbs, $regex, $data);
            }
        };

        $route_collector_opts = [
            'cacheFile' => From::VAR->dir() . '.route-cache',
            'cacheDisabled' => $this->getConfig()->getRouting()['use_cache'] ?? true,
        ];

        $this->setDispatcher(
            cachedDispatcher(
                $route_collector,
                $route_collector_opts
            )
        );

        $match = $this->getDispatcher()->dispatch(
            $this->getRequest()->getMethod(),
            $this->getRequest()->getPath()
        );

        return $this->_processRoute($match);
    }

    /**
     * Catch invalid requests and map to appropriate error handlers
     *
     * @param mixed $matched_route The route which matched the current request.
     *
     * @return mixed The class, method and parameters that are matched to the request.
     */
    private function _processRoute(mixed $matched_route): mixed
    {
        // trigger_error(print_r(array($this->getRequest()->getPath(), $matched_route), true));
        switch ($matched_route[0]) {
            case Dispatcher::METHOD_NOT_ALLOWED:
                return [null, '_sling405', ['use_vars' => true], $matched_route[1]];

            case Dispatcher::NOT_FOUND:
                return [null, '_sling404', null, null];

            case Dispatcher::FOUND:
                return array_merge($matched_route[1], [$matched_route[2]]);
        }
    }
}
