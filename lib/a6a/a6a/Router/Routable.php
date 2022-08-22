<?php

namespace a6a\a6a\Router;

/**
 * The Routable interface
 *
 * A routable class has an opportunity to register routes with the router and optionally provide
 * methods to service those routes.
 */
interface Routable
{
    /**
     * Provides a list of routes to register with the Router to be serviced by this object.
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed;
}
