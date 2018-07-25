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

use Psr\Http\Message\ServerRequestInterface;
use Tlumx\Router\Result;

/**
* Interface, which router class must implement.
*/
interface RouterInterface
{
    /**
    * Match a request (URL path) with a set of routes.
    *
    * @param  ServerRequestInterface $request The current HTTP request object
    *
    * @return RouteResult
    */
    public function match(ServerRequestInterface $request):Result;

    /**
    * Build the URI for a given route
    *
    * @param string $name Route name
    * @param array  $data Named optional argument replacement data
    * @param array  $queryData  optional query string parameters
    */
    public function uriFor(string $name, array $data = [], array $queryData = []):string;
}
