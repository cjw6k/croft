<?php

namespace a6a\a6a\Router;

/**
 * The Router\Route class is a helper to make working with routes easier
 */
class Route
{
    /**
     * The packaged route.
     */
    private mixed $route = null;

    /**
     * The priority of this route.
     */
    private int $priority = 50;

    /**
     * Combine HTTP verb, path regex, controller method, and extra data into a route definition
     *
     * @param array<string>|string $verb The allowed HTTP methods for this route.
     * @param string $regex The regex to match a URL to this route.
     * @param string $method The method to run when matched; a public method of the class
     *                                    where the route is specified.
     * @param mixed|null $extra Optional extra data to pass through to the controller.
     * @param string|null $controller Optional controller for this route.
     * @param int $priority Optional priority, higher number is registered sooner.
     */
    public function __construct(array|string $verb, string $regex, string $method, mixed $extra = null, ?string $controller = null, int $priority = 50)
    {
        $this->route = [
            $verb,
            $regex,
            [
                $controller,
                $method,
                $extra,
            ],
        ];

        $this->priority = $priority;
    }

    /**
     * Set the controller class that provides the action method of this route
     *
     * @param string $class_name The name of the class that provides the action method.
     */
    public function setController(string $class_name): void
    {
        $this->route[2][0] = $class_name;
    }

    /**
     * Package the route details for consumption by the route dispatcher
     *
     * @return mixed The route in a form fit for routing.
     */
    public function pack(): mixed
    {
        return $this->route;
    }

    /**
     * Provide the priority number of this route.
     *
     * @return int The priority.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
