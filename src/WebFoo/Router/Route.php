<?php
/**
 * The Route class is herein defined.
 *
 * @package WebFoo\Router
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Router;

/**
 * The Router\Route class is a helper to make working with routes easier
 */
class Route
{

    /**
     * The packaged route.
     *
     * @var mixed
     */
    private $_route = null;

    /**
     * The priority of this route.
     *
     * @var integer
     */
    private $_priority = 50;

    /**
     * Combine HTTP verb, path regex, controller method, and extra data into a route definition
     *
     * @param string[]|string $verb       The allowed HTTP methods for this route.
     * @param string          $regex      The regex to match a URL to this route.
     * @param string          $method     The method to run when matched; a public method of the class
     *                                    where the route is specified.
     * @param mixed|null      $extra      Optional extra data to pass through to the controller.
     * @param string|null     $controller Optional controller for this route.
     * @param integer         $priority   Optional priority, higher number is registered sooner.
     */
    public function __construct($verb, string $regex, string $method, $extra = null, $controller = null, int $priority = 50)
    {
        $this->_route = array(
        $verb,
        $regex,
        array(
        $controller,
        $method,
        $extra
        ),
        );

        $this->_priority = $priority;
    }

    /**
     * Set the controller class that provides the action method of this route
     *
     * @param string $class_name The name of the class that provides the action method.
     *
     * @return void
     */
    public function setController(string $class_name)
    {
        $this->_route[2][0] = $class_name;
    }

    /**
     * Package the route details for consumption by the route dispatcher
     *
     * @return mixed The route in a form fit for routing.
     */
    public function pack()
    {
        return $this->_route;
    }

    /**
     * Provide the priority number of this route.
     *
     * @return integer The priority.
     */
    public function getPriority()
    {
        return $this->_priority;
    }

}
