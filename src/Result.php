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

/**
* Result - object of this class must be returning by Router matching.
*/
class Result
{

    const ROUTE_FOUND = 1;

    const ROUTE_NOT_FOUND = 0;

    const ROUTE_METHOD_NOT_ALLOWED = 2;

    /**
    * Route name
    *
    * @var null|string
    */
    protected $routeName;

    /**
    * Route params
    *
    * @var array
    */
    protected $routeParams = [];

    /**
    * Route allowed methods
    *
    * @var string[]
    */
    protected $routeAllowedMethods = [];

    /**
    * Route middlewares
    *
    * @var array
    */
    protected $routeMiddlewares = [];

    /**
    * Route handler
    *
    * @var array
    */
    protected $routeHandler = [];

    /**
    * Router matching status
    *
    * @var int
    */
    protected $status;

    /**
    * Constructor not allow.
    */
    private function __construct()
    {
    }

    /**
    * Create an instance if matching was success
    *
    * @param string $routeName
    * @param array $routeParams
    * @param array $routeAllowedMethods
    * @param array $routeMiddlewares
    * @param array $routeHandler
    * @return self
    */
    public static function createSuccess(
        string $routeName,
        array $routeParams = [],
        array $routeAllowedMethods = [],
        array $routeMiddlewares = [],
        array $routeHandler = []
    ):self {
        $result                         = new self();
        $result->routeName              = $routeName;
        $result->routeParams            = $routeParams;
        $result->routeAllowedMethods    = $routeAllowedMethods;
        $result->routeMiddlewares       = $routeMiddlewares;
        $result->routeHandler           = $routeHandler;
        $result->status                 = $result::ROUTE_FOUND;
        return $result;
    }

    /**
    * Create an instance if matching was not success
    *
    * @return self
    */
    public static function createNotFound():self
    {
        $result         = new self();
        $result->status = $result::ROUTE_NOT_FOUND;
        return $result;
    }

    /**
    * Create an instance if method not allowed
    *
    * @param array $routeAllowedMethods
    * @return self
    */
    public static function createMethodNotAllowed(array $routeAllowedMethods):self
    {
        $result                         = new self();
        $result->routeAllowedMethods    = $routeAllowedMethods;
        $result->status                 = $result::ROUTE_METHOD_NOT_ALLOWED;
        return $result;
    }

    /**
     * Is successful result?
     *
     * @return bool
     */
    public function isSuccess() : bool
    {
        return $this->status == self::ROUTE_FOUND;
    }

    /**
     * Is Not Found?
     *
     * @return bool
     */
    public function isNotFound() : bool
    {
        return (!$this->isSuccess());
    }

    /**
     * Is failure HTTP method result?
     *
     * @return bool
     */
    public function isMethodNotAllowed() : bool
    {
        return ($this->status == self::ROUTE_METHOD_NOT_ALLOWED);
    }


    /**
    * Return the matched route name.
    *
    * @return null|string
    */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
    * Returns the matched route params.
    *
    * @return array
    */
    public function getParams()
    {
        return $this->routeParams;
    }

    /**
    * Returns the allowed methods for the route.
    *
    * @return string[] HTTP methods
    */
    public function getAllowedMethods()
    {
        return $this->routeAllowedMethods;
    }

    /**
    * Returns the middlewares for result route.
    *
    * @return string[] middlewares class/services names
    */
    public function getRouteMiddlewares()
    {
        return $this->routeMiddlewares;
    }

    /**
    * Returns the handler array for result route.
    *
    * @return array
    */
    public function getRouteHandler()
    {
        return $this->routeHandler;
    }
}
