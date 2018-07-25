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

use FastRoute\DataGenerator;
use FastRoute\RouteParser;

/**
* Collection of routes, used by the router
*/
class RouteCollector
{
    /**
    * Set of routes
    *
    * @var array
    */
    protected $routes = [];

    /**
    * Set of routes groups
    *
    * @var array
    */
    protected $groups = [];

    /**
    * Add route to collection.
    *
    * @param string $name
    * @param array $methods
    * @param string $pattern
    * @param array $middlewares
    * @param array $handler
    * @param string $group
    */
    public function addRoute(
        string $name,
        array $methods,
        string $pattern,
        array $middlewares,
        array $handler,
        string $group = null
    ) {
        $this->routes[$name] = [
            'methods' => $methods,
            'pattern' => $pattern,
            'middlewares' => $middlewares,
            'handler' => $handler,
            'group' => $group
        ];
    }

    /**
    * Add group for routes to collection.
    *
    * @param string $name
    * @param string $prefix
    * @param array $middlewares
    */
    public function addGroup(string $name, string $prefix = '', array $middlewares = [])
    {
        $this->groups[$name] = [$prefix, $middlewares];
    }

    /**
    * Generate routes data for FastRouter
    *
    * @param RouteParser $routeParser
    * @param DataGenerator $dataGenerator
    * @return array
    */
    public function generateRoutesData(RouteParser $routeParser, DataGenerator $dataGenerator)
    {
        $routes = [];

        foreach ($this->routes as $name => $route) {
            if ($route['group']) {
                if (!isset($this->groups[$route['group']])) {
                    throw new \LogicException(sprintf(
                        'The route group "%s" is not found',
                        $route['group']
                    ));
                }
                $pattern = $this->groups[$route['group']][0] . $route['pattern'];
                $middlewares = array_merge($this->groups[$route['group']][1], $route['middlewares']);
            } else {
                $pattern = $route['pattern'];
                $middlewares = $route['middlewares'];
            }

            $routeDatas = $routeParser->parse($pattern);
            $this->addToDataGenerator($route['methods'], $routeDatas, $name, $dataGenerator);

            $routes[$name] = [
                'datas' => $routeDatas,
                'methods' => $route['methods'],
                'pattern' => $pattern,
                'middlewares' => $middlewares,
                'handler' => $route['handler'],
                'group' => $route['group']
            ];
        }

        return ['routes' => $routes, 'dispatch_data' => $dataGenerator->getData()];
    }

    /**
    * Set route data to DataGenerator
    *
    * @param array $methods
    * @param array $routeDatas
    * @param DataGenerator $dataGenerator
    * @return DataGenerator
    */
    protected function addToDataGenerator(
        array $methods,
        array $routeDatas,
        string $routeName,
        DataGenerator $dataGenerator
    ) : DataGenerator {
        foreach ($methods as $method) {
            foreach ($routeDatas as $routeData) {
                $dataGenerator->addRoute($method, $routeData, $routeName);
            }
        }

        return $dataGenerator;
    }
}
