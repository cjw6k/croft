<?php
/**
 * The WebFoo class is herein defined.
 *
 * @package WebFoo
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Exception\Redirect;
use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Response\HTTPLinkable;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Setup\Setup;
use \cjw6k\WebFoo\Setup\Setupable;
use \cjw6k\WebFoo\Storage\Storable;
use \cjw6k\WebFoo\Storage\Store;


/**
 * The WebFoo Class is the main slingin' thinger.
 */
class WebFoo
{

    use Aether;

    /**
     * Construct the web stuff slinging thinger
     *
     * @param ServiceInterface[]        $services   Core services of WebFoo.
     * @param ExtensionInterface[]|null $extensions Optional extensions to WebFoo.
     */
    public function __construct(array $services, $extensions = null)
    {
        foreach($services as $service_name => $service){
            $this->_service($service_name, $service);
        }

        foreach($this->getServices() as $service){
            $this->_serviceRoutes($service);
            $this->_serviceStores($service);
        }

        // Throw exception if core services are missing

        if(!empty($extensions)) {
            foreach($extensions as $extension){
                $this->_extend($extension);
            }
        }

        if($this->hasHTTPLinks()) {
            $this->getResponse()->mergeHeaders('Link: ' . implode(',', $this->getHTTPLinks()));
        }
    }

    /**
     * Provide core functionality with the provided services
     *
     * @param string|integer   $service_name The name to use when registering this service
     *                                       with webfoo. If not a string, will use the
     *                                       class name as the name.
     * @param ServiceInterface $service      The service to register.
     *
     * @return void
     */
    private function _service($service_name, ServiceInterface $service)
    {
        if(!is_string($service_name)) {
            $class_path_parts = explode('\\', get_class($service));
            $class_index = $this->_underscore(array_pop($class_path_parts));
            $this->mergeServices($class_index);
            $this->_data[$class_index] = $service;
            return;
        }

        $class_index = $this->_underscore($service_name);
        $this->mergeServices($class_index);
        $this->_data[$class_index] = $service;
    }

    /**
     * Add service routes to Router
     *
     * @param string $class_index The index into the data array where this service is referenced.
     *
     * @return void
     */
    private function _serviceRoutes(string $class_index)
    {
        $service = $this->_data[$class_index];

        if(!($service instanceof Routable)) {
            return;
        }

        $routes = $service->getRoutes();
        if(!$routes) {
            return;
        }

        $router = $this->getRouter();
        foreach($routes as $route){
            if(!($route instanceof Route)) {
                // throw exception
                continue;
            }
            $route->setController($class_index);
            $router->mergeRoutes($route);
        }
    }

    /**
     * Add service stores to Storage
     *
     * @param string $class_index The index into the data array where this service is referenced.
     *
     * @return void
     */
    private function _serviceStores(string $class_index)
    {
        $service = $this->_data[$class_index];

        if(!($service instanceof Storable)) {
            return;
        }

        $stores = $service->getStores();
        if(!$stores) {
            return;
        }

        $storage = $this->getStorage();
        foreach($stores as $store){
            if(!($store instanceof Store)) {
                // throw exception
                continue;
            }
            $storage->register($store);
        }
    }

    /**
     * Extend functionality with the provided extension
     *
     * @param ExtensionInterface $extension The extension.
     *
     * @return void
     */
    private function _extend(ExtensionInterface $extension)
    {
        $class_path_parts = explode('\\', get_class($extension));
        $class_index = $this->_underscore(array_pop($class_path_parts));

        $this->mergeExtensions($class_index);

        $this->_data[$class_index] = $extension;

        $this->_extensionRoutes($class_index, $extension);
        $this->_extensionLinks($extension);
        $this->getAsync()->register($extension);

    }

    /**
     * Add extension routes to Router
     *
     * @param string             $class_index The index into the data array where this
     *                                        extension is referenced.
     * @param ExtensionInterface $extension   The extension.
     *
     * @return void
     */
    private function _extensionRoutes(string $class_index, ExtensionInterface $extension)
    {
        if(!($extension instanceof Routable)) {
            return;
        }

        $routes = $extension->getRoutes();
        if(!$routes) {
            return;
        }

        $router = $this->getRouter();
        foreach($routes as $route){
            if(!($route instanceof Route)) {
                // throw exception
                continue;
            }
            $route->setController($class_index);
            $router->mergeRoutes($route);
        }
    }

    /**
     * Add extension links to Response
     *
     * @param ExtensionInterface $extension The extension.
     *
     * @return void
     */
    private function _extensionLinks(ExtensionInterface $extension)
    {
        if(!($extension instanceof HTTPLinkable)) {
            return;
        }

        $links = $extension->getHTTPLinks();
        if(!$links) {
            return;
        }

        foreach($links as $link){
            $this->mergeHTTPLinks($link);
        }
    }

    /**
     * Sling some web stuff with this thinger.
     *
     * @return void
     */
    public function sling()
    {
        $response = $this->getResponse();

        try {
            $this->getSession()->start();

            list($controller, $method, $extras, $vars) = $this->getRouter()->route();

            $that = $this;
            if(!is_null($controller)) {
                $that = $this->_data[$controller];
            }

            $template = (isset($extras['use_vars']) && true == $extras['use_vars']) ? $that->$method($vars) : $that->$method();
            if($template) {
                $this->_includeTemplate($template);
            }

        } catch (Redirect $redirect){
            $response->mergeHeaders('Location: ' . $redirect->getMessage());
            $response->send();
            return;
        }

        $response->send();

        $this->getAsync()->run();
    }

    /**
     * Control file not found requests
     *
     * @return string[] The template to render, with alternate.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _sling404()
    {
        $this->getResponse()->setCode(404);
        $this->getConfig()->setTitle(
            $this->getConfig()->getTitle() . ' - File Not Found'
        );

        return array('404.php', 'default.php');
    }

    /**
     * Control method not allowed requests
     *
     * @param string[] $allowed_methods The allowed HTTP methods for this URL.
     *
     * @return string[] The template to render, with alternate.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _sling405(array $allowed_methods)
    {
        $this->setAllowedMethods($allowed_methods);

        $this->getResponse()->setCode(405);
        $this->getConfig()->setTitle(
            $this->getConfig()->getTitle() . ' - Method Not Allowed'
        );

        return array('405.php', 'default.php');
    }

    /**
     * Setup an admin login
     *
     * @param array $argv The arguments provided on the comment line to setup.php.
     *
     * @return integer The exit status code.
     */
    public function setup(array $argv) : int
    {
        $setup = new Setup($this->getRequest());
        if(!$setup->prerequisites($argv)) {
            return 1;
        }

        $extensions = $this->getExtensions();
        if($extensions) {
            foreach($extensions as $extension){
                if($this->_data[$extension] instanceof Setupable) {
                    if(!$this->_data[$extension]->setup($setup)) {
                        return 1;
                    }
                }
            }
        }

        return $setup->configure();
    }

    /**
     * Output the webfoo controls HTML.
     *
     * @return void
     */
    public function webfooControls()
    {
        if(!$this->getSession()->isLoggedIn()) {
            return;
        }

        $this->_includeTemplate('webfoo_controls.php');
    }

    /**
     * Send HTML to the client from a template file
     *
     * @param string|string[] $template The filename to include or a pair of primary and alternate.
     *
     * @return void
     */
    private function _includeTemplate($template)
    {
        if(is_array($template)) {
            $this->_includeTemplateWithAlternate($template[0], $template[1]);
            return;
        }
        $this->_includeTemplateWithAlternate($template);
    }

    /**
     * Send HTML to the client from a template file
     *
     * @param string $template  The filename to load.
     * @param string $alternate The filename to load from default templates when the requested
     *                          template is missing from the local templates.
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude
     */
    private function _includeTemplateWithAlternate(string $template, string $alternate = '')
    {
        if(file_exists(TEMPLATES_LOCAL . $template)) {
            /**
             * A file_exists check has succeeded at runtime.
             *
             * @psalm-suppress MissingFile
             */
            include TEMPLATES_LOCAL . $template;
            return;
        }

        if(!empty($alternate)) {
            include TEMPLATES_DEFAULT . $alternate;
            return;
        }

        include TEMPLATES_DEFAULT . $template;
    }

}
