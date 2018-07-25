<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/tlumx-router
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/tlumx-router/blob/master/LICENSE  (MIT License)
 */
namespace Tlumx\Router;

use Tlumx\Router\Result;
use Psr\Http\Message\ServerRequestInterface;
use FastRoute\RouteParser\Std as StdParser;
use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;

/**
* Router - main class of routing.
*/
class Router implements RouterInterface
{
    /**
    * Callback for defining the routes.
    *
    * @var callable
    */
    protected $routeDefinitionCallback;

    /**
    * Is enabled caching?
    *
    * @var bool
    */
    protected $cacheEnabled;

    /**
    * Cache file.
    *
    * @var string
    */
    protected $cacheFile;

    /**
    * Is routes data is prepared to match?
    *
    * @var bool
    */
    protected $prepared = false;

    /**
    * Routes wirh parses a route string.
    *
    * @var array
    */
    protected $routes = [];

    /**
    * Dispatch data for router dispatcher.
    *
    * @var array
    */
    protected $dispatchData = [];


    /**
    * Constructor
    *
    * @param callable $routeDefinitionCallback
    * @param bool $cacheEnabled
    * @param string $cacheFile
    */
    public function __construct(
        callable $routeDefinitionCallback,
        bool $cacheEnabled = false,
        string $cacheFile = 'data/cache/fastroute.php.cache'
    ) {
        $this->routeDefinitionCallback = $routeDefinitionCallback;
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheFile = $cacheFile;
    }

    /**
    * Return array of route definition
    *
    * @param string $name
    * @return array
    * @throws RuntimeException
    */
    public function getRouteDefinition(string $name):array
    {
        $this->prepare();

        if (!isset($this->routes[$name]) || !is_array($this->routes[$name])) {
            throw new \RuntimeException(sprintf(
                'Route "%s" does not exist or not array.',
                $name
            ));
        }

        return $this->routes[$name];
    }

    /**
    * Get route group name
    *
    * @param string $routeName
    * @return string
    */
    public function getRouteGroupName(string $routeName):string
    {
        $route = $this->getRouteDefinition($routeName);
        return $route['group'];
    }

    /**
    * Prepare routes data & save/load cache
    */
    protected function prepare() : void
    {
        if ($this->prepared) {
            return;
        }

        if ($this->cacheEnabled && file_exists($this->cacheFile)) {
            $this->loadFromCache();
            $this->prepared = true;
            return;
        }

        $routeCollector = new RouteCollector();
        $routeDefinitionCallback = $this->routeDefinitionCallback;
        $routeDefinitionCallback($routeCollector);

        $data = $routeCollector->generateRoutesData(
            new StdParser(),
            new DataGeneratorGroupCountBased()
        );
        $this->routes = $data['routes'];
        $this->dispatchData = $data['dispatch_data'];

        if ($this->cacheEnabled) {
            $this->saveToCache($data);
        }

        $this->prepared = true;
    }

    /**
    * Load data from cache.
    *
    * @throws RuntimeException
    */
    protected function loadFromCache() : void
    {
        $data = include $this->cacheFile;
        if (!is_array($data) ||
            !isset($data['routes']) ||
            !is_array($data['routes']) ||
            !isset($data['dispatch_data']) ||
            !is_array($data['dispatch_data'])
        ) {
            throw new \RuntimeException(sprintf(
                'Invalid cache file "%s"',
                $this->cacheFile
            ));
        }

        $this->routes = $data['routes'];
        $this->dispatchData = $data['dispatch_data'];
    }

    /**
    * Save data to cache.
    *
    * @return int|false
    * @throws RuntimeException
    */
    protected function saveToCache(array $data)
    {
        $cacheDir = dirname($this->cacheFile);

        if (! is_dir($cacheDir) || ! is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf(
                'Invalid cache directory "%s": directory does not exist or not writable.',
                $cacheDir
            ));
        }

        return file_put_contents(
            $this->cacheFile,
            '<?php return ' . var_export($data, true) . ';'
        );
    }

    /**
    * Match a request (URL path) with a set of routes.
    *
    * @param  ServerRequestInterface $request The current HTTP request object
    *
    * @return RouteResult
    */
    public function match(ServerRequestInterface $request):Result
    {
        $this->prepare();

        $path       = '/' . ltrim($request->getUri()->getPath(), '/');
        $method     = $request->getMethod();
        $dispatcher = new DispatcherGroupCountBased($this->dispatchData);
        $routeInfo  = $dispatcher->dispatch($method, $path);

        switch ($routeInfo[0]) {
            case DispatcherGroupCountBased::NOT_FOUND:
                // ... 404 Not Found
                $result = Result::createNotFound();
                break;
            case DispatcherGroupCountBased::METHOD_NOT_ALLOWED:
                // ... 405 Method Not Allowed
                $allowedMethods = $routeInfo[1];
                $result = Result::createMethodNotAllowed($allowedMethods);
                break;
            case DispatcherGroupCountBased::FOUND:
                // ... OK
                $routeName = $routeInfo[1];
                $params = $routeInfo[2];
                $route = $this->getRouteDefinition($routeName);
                $methods = $route['methods'];
                $middlewares = $route['middlewares'];
                $handler = $route['handler'];
                $result = Result::createSuccess(
                    $routeName,
                    $params,
                    $methods,
                    $middlewares,
                    $handler
                );
                break;
        }

        return $result;
    }

    /**
    * Build the URI for a given route
    * The code for this method is taken from Slim framework (https://slimframework.com) Router:
    * https://github.com/slimphp/Slim/blob/3.x/Slim/Router.php
    *
    * @param string $name Route name
    * @param array  $data Named optional argument replacement data
    * @param array  $queryData  optional query string parameters
    * @return string
    */
    public function uriFor(string $name, array $data = [], array $queryParams = []):string
    {
        $this->prepare();

        $route = $this->getRouteDefinition($name);
        $routeDatas = $route['datas'];

        $routeDatas = array_reverse($routeDatas);

        // this part code from Slim 3 Router
        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new \InvalidArgumentException(sprintf(
                'Missing data for URL segment: "%s".',
                $segmentName
            ));
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}
