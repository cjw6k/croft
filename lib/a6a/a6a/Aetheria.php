<?php

namespace a6a\a6a;

use a6a\a6a\Async\AsyncInterface;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Extension\ExtensionInterface;
use a6a\a6a\Media\MediaInterface;
use a6a\a6a\Page\PageInterface;
use a6a\a6a\Post\PostInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Response\HTTPLinkable;
use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Router\RouterInterface;
use a6a\a6a\Service\ServiceInterface;
use a6a\a6a\Session\SessionInterface;
use a6a\a6a\Setup\Setupable;
use a6a\a6a\Setup\SetupInterface;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\StorageInterface;
use a6a\a6a\Storage\Store;
use A6A\Aether\Aether;
use League\CLImate\CLImate;

use function is_string;
use function explode;
use function get_class;
use function array_pop;
use function is_null;
use function is_array;
use function file_exists;

class Aetheria
{
    use Aether;

    /**
     * Construct the web stuff slinging thinger
     *
     * @param array<ExtensionInterface>|null $extensions Optional extensions to WebFoo.
     */
    public function __construct(
        AsyncInterface $async,
        ConfigInterface $config,
        MediaInterface $media,
        PageInterface $page,
        PostInterface $post,
        RequestInterface $request,
        ResponseInterface $response,
        RouterInterface $router,
        SessionInterface $session,
        StorageInterface $storage,
        //        $extensions = null
    ) {
        //        $services = compact(
        ////            'async',
        ////            'config',
        ////            'media',
        ////            'page',
        ////            'post',
        ////            'request',
        ////            'response',
        ////            'router',
        ////            'session',
        ////            'storage',
        //        );
        //        $services = [];
        //        foreach($services as $service_name => $service){
        //            $this->_service($service_name, $service);
        //        }
        //
        //        foreach($this->getServices() as $service){
        //            $this->_serviceRoutes($service);
        //            $this->_serviceStores($service);
        //        }
        //
        //        if(!empty($extensions)) {
        //            foreach($extensions as $extension){
        //                $this->_extend($extension);
        //            }
        //        }
        //
        //        if($this->hasHTTPLinks()) {
        //            $this->getResponse()->mergeHeaders('Link: ' . implode(',', $this->getHTTPLinks()));
        //        }
        $this->_service('config', $config);
        $this->_service('request', $request);
        $this->_service('response', $response);
        $this->_service('router', $router);
        $this->_service('session', $session);
        $this->_service('storage', $storage);
        $this->_service('async', $async);
        $this->_service('media', $media);
        $this->_service('post', $post);
        $this->_service('page', $page);

        foreach ($this->getServices() as $service) {
            $this->_serviceRoutes($service);
            $this->_serviceStores($service);
        }
    }

    /**
     * Provide core functionality with the provided services
     *
     * @param string|int $service_name The name to use when registering this service
 * with webfoo. If not a string, will use the
 * class name as the name.
     * @param ServiceInterface $service The service to register.
     */
    private function _service(string|int $service_name, ServiceInterface $service): void
    {
        if (! is_string($service_name)) {
            $class_path_parts = explode('\\', $service::class);
            $class_index = $this->asLabel(array_pop($class_path_parts));
            $this->mergeServices($class_index);
            $this->data[$class_index] = $service;

            return;
        }

        $class_index = $this->asLabel($service_name);
        $this->mergeServices($class_index);
        $this->data[$class_index] = $service;
    }

    /**
     * Add service routes to Router
     *
     * @param string $class_index The index into the data array where this service is referenced.
     */
    private function _serviceRoutes(string $class_index): void
    {
        $service = $this->data[$class_index];

        if (! ($service instanceof Routable)) {
            return;
        }

        $routes = $service->getRoutes();

        if (! $routes) {
            return;
        }

        $router = $this->getRouter();

        foreach ($routes as $route) {
            if (! ($route instanceof Route)) {
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
     */
    private function _serviceStores(string $class_index): void
    {
        $service = $this->data[$class_index];

        if (! ($service instanceof Storable)) {
            return;
        }

        $stores = $service->getStores();

        if (! $stores) {
            return;
        }

        $storage = $this->getStorage();

        foreach ($stores as $store) {
            if (! ($store instanceof Store)) {
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
     */
    private function _extend(ExtensionInterface $extension): void
    {
        $class_path_parts = explode('\\', $extension::class);
        $class_index = $this->_underscore(array_pop($class_path_parts));

        $this->mergeExtensions($class_index);

        $this->data[$class_index] = $extension;

        $this->_extensionRoutes($class_index, $extension);
        $this->_extensionLinks($extension);
        $this->getAsync()->register($extension);
    }

    /**
     * Add extension routes to Router
     *
     * @param string $class_index The index into the data array where this
     *                                        extension is referenced.
     * @param ExtensionInterface $extension The extension.
     */
    private function _extensionRoutes(string $class_index, ExtensionInterface $extension): void
    {
        if (! ($extension instanceof Routable)) {
            return;
        }

        $routes = $extension->getRoutes();

        if (! $routes) {
            return;
        }

        $router = $this->getRouter();

        foreach ($routes as $route) {
            if (! ($route instanceof Route)) {
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
     */
    private function _extensionLinks(ExtensionInterface $extension): void
    {
        if (! ($extension instanceof HTTPLinkable)) {
            return;
        }

        $links = $extension->getHTTPLinks();

        if (! $links) {
            return;
        }

        foreach ($links as $link) {
            $this->mergeHTTPLinks($link);
        }
    }

    /**
     * Sling some web stuff with this thinger.
     */
    public function sling(): void
    {
        $response = $this->getResponse();

        try {
            $this->getSession()->start();

            [$controller, $method, $extras, $vars] = $this->getRouter()->route();

            $that = $this;

            if (! is_null($controller)) {
                $that = $this->data[$controller];
            }

            $template = (isset($extras['use_vars']) && $extras['use_vars'] == true)
                ? $that->$method($vars)
                : $that->$method();

            if ($template) {
                $this->_includeTemplate($template);
            }
        } catch (Redirect $redirect) {
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
     * @return array<string> The template to render, with alternate.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _sling404(): array
    {
        $this->getResponse()->setCode(404);
        $this->getConfig()->setTitle(
            $this->getConfig()->getTitle() . ' - File Not Found'
        );

        return ['404.php', 'default.php'];
    }

    /**
     * Control method not allowed requests
     *
     * @param array<string> $allowed_methods The allowed HTTP methods for this URL.
     *
     * @return array<string> The template to render, with alternate.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _sling405(array $allowed_methods): array
    {
        $this->setAllowedMethods($allowed_methods);

        $this->getResponse()->setCode(405);
        $this->getConfig()->setTitle(
            $this->getConfig()->getTitle() . ' - Method Not Allowed'
        );

        return ['405.php', 'default.php'];
    }

    /**
     * Setup an admin login
     *
     * @param array $argv The arguments provided on the comment line to setup.php.
     *
     * @return int The exit status code.
     */
    public function setup(CLImate $cli, SetupInterface $setup): int
    {
        $extensions = $this->getExtensions();

        if ($extensions) {
            foreach ($extensions as $extension) {
                if (! ($this->data[$extension] instanceof Setupable)) {
                    continue;
                }

                if (! $this->data[$extension]->setup($setup)) {
                    return 1;
                }
            }
        }

        return $setup->configure($cli);
    }

    /**
     * Output the webfoo controls HTML.
     */
    public function webfooControls(): void
    {
        if (! $this->getSession()->isLoggedIn()) {
            return;
        }

        $this->_includeTemplate('webfoo_controls.php');
    }

    /**
     * Send HTML to the client from a template file
     *
     * @param string|array<string> $template The filename to include or a pair of primary and alternate.
     */
    private function _includeTemplate(string|array $template): void
    {
        if (is_array($template)) {
            $this->_includeTemplateWithAlternate($template[0], $template[1]);

            return;
        }

        $this->_includeTemplateWithAlternate($template);
    }

    /**
     * Send HTML to the client from a template file
     *
     * @param string $template The filename to load.
     * @param string $alternate The filename to load from default templates when the requested
     *                          template is missing from the local templates.
     *
     * @psalm-suppress UnresolvableInclude
     */
    private function _includeTemplateWithAlternate(string $template, string $alternate = ''): void
    {
        if (file_exists(From::TEMPLATES___LOCAL->dir() . $template)) {
            /**
             * A file_exists check has succeeded at runtime.
             *
             * @psalm-suppress MissingFile
             */
            include From::TEMPLATES___LOCAL->dir() . $template;

            return;
        }

        if (! empty($alternate)) {
            include From::TEMPLATES___DEFAULT->dir() . $alternate;

            return;
        }

        include From::TEMPLATES___DEFAULT->dir() . $template;
    }
}
