<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/tlumx-router
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/tlumx-router/blob/master/LICENSE  (MIT License)
 */
namespace Tlumx\Tests\Router;

use Tlumx\Router\Router;
use Tlumx\Router\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    public function createRequest(string $uriPath = '/', string $method = 'GET')
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn($uriPath);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->will(function () use ($uri) {
            return $uri->reveal();
        });
        $request->getMethod()->willReturn($method);

        return $request->reveal();
    }

    public function getRouteDefinitionCallback()
    {
        return function (RouteCollector $r) {
            $r->addRoute(
                'foo',
                ['GET','POST'],
                '/foo',
                ['midd1', 'midd2'],
                ['_controller' => 'home','_action' => 'index']
            );
            $r->addRoute(
                'article',
                ['GET'],
                '/articles/{id:\d+}[/{title}]',
                ['midd1', 'midd2'],
                ['article_handler'],
                'adm'
            );
            $r->addGroup('adm', '/admin', ['adm_midd1', 'adm_midd2']);
        };
    }

    public function testImplements()
    {
        $this->assertInstanceOf(
            'Tlumx\Router\RouterInterface',
            new Router(function () {
            })
        );
    }

    public function testMatchSuccessResult()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $request = $this->createRequest('/admin/articles/10/my-story', 'GET');
        $result = $router->match($request);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertEquals('article', $result->getRouteName());
        $this->assertEquals(['id' => '10', 'title' => 'my-story'], $result->getParams());
        $this->assertEquals(['GET'], $result->getAllowedMethods());
        $this->assertEquals(['adm_midd1', 'adm_midd2', 'midd1', 'midd2'], $result->getRouteMiddlewares());
        $this->assertEquals(['article_handler'], $result->getRouteHandler());
    }

    public function testMatchMethodNotAllowedResult()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $request = $this->createRequest('/admin/articles/10/my-story', 'POST');
        $result = $router->match($request);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isNotFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertNull($result->getRouteName());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals(['GET'], $result->getAllowedMethods());
        $this->assertEquals([], $result->getRouteMiddlewares());
        $this->assertEquals([], $result->getRouteHandler());
    }

    public function testMatchNotFound()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $request = $this->createRequest('/no-exist-uri', 'GET');
        $result = $router->match($request);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertNull($result->getRouteName());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals([], $result->getAllowedMethods());
        $this->assertEquals([], $result->getRouteMiddlewares());
        $this->assertEquals([], $result->getRouteHandler());
    }

    public function testRouteDefinition()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);
        $routeDefinition = $router->getRouteDefinition('article');
        unset($routeDefinition['datas']);
        $this->assertEquals([
            'methods' => ['GET'],
            'pattern' => '/admin/articles/{id:\d+}[/{title}]',
            'middlewares' => ['adm_midd1', 'adm_midd2', 'midd1', 'midd2'],
            'handler' => ['article_handler'],
            'group' => 'adm'
        ], $routeDefinition);
    }

    public function testInvalidGetRouteDefinition()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Route "%s" does not exist or not array.',
            'not_exist_route_name'
        ));

        $routeDefinition = $router->getRouteDefinition('not_exist_route_name');
    }

    public function testGetRouteGroupName()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);
        $this->assertEquals('adm', $router->getRouteGroupName('article'));
    }

    public function testGetRouteGroupNameNotExist()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Route "%s" does not exist or not array.',
            'not_exist_route_name'
        ));

        $this->assertEquals('adm', $router->getRouteGroupName('not_exist_route_name'));
    }

    public function testUriFor()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $this->assertEquals(
            '/foo',
            $router->uriFor('foo')
        );
        $this->assertEquals(
            '/foo?x=100&y=z',
            $router->uriFor('foo', ['a' => 'b'], ['x' => 100, 'y' => 'z'])
        );
        $this->assertEquals(
            '/admin/articles/10/my-story?x=100&y=z',
            $router->uriFor('article', ['id' => '10', 'title' => 'my-story'], ['x' => 100, 'y' => 'z'])
        );
        $this->assertEquals(
            '/admin/articles/10',
            $router->uriFor('article', ['id' => '10'])
        );
    }

    public function testInvalidUriFor()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing data for URL segment: "%s".',
            'id'
        ));

        $router->uriFor('article');
    }

    public function testBaseSettingsCacheRoutes()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback);

        $class = new \ReflectionClass($router);

        $property = $class->getProperty('cacheEnabled');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue($router));

        $property = $class->getProperty('cacheFile');
        $property->setAccessible(true);
        $this->assertEquals('data/cache/fastroute.php.cache', $property->getValue($router));
    }

    public function testSetCacheRoutes()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $router = new Router($routeDefinitionCallback, true, 'router.cache');

        $class = new \ReflectionClass($router);

        $property = $class->getProperty('cacheEnabled');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($router));

        $property = $class->getProperty('cacheFile');
        $property->setAccessible(true);
        $this->assertEquals('router.cache', $property->getValue($router));
    }

    public function testInvalidCacheDir()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheDir = dirname(__FILE__) . uniqid(microtime(true));
        $cacheFile = $cacheDir . '/' . uniqid(microtime(true));
        $router = new Router($routeDefinitionCallback, true, $cacheFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache directory "%s": directory does not exist or not writable.',
            $cacheDir
        ));

        // for calling cache function
        $router->uriFor('foo');
    }

    public function testInvalidSaveCacheFileExistsAndIsNotWritable()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = sys_get_temp_dir() . '/' . uniqid(microtime(true))."tlumxframework_tmp_cache";
        $router = new Router($routeDefinitionCallback, true, $cacheFile);

        $router->uriFor('foo');

        $cacheData = include $cacheFile;
        $cacheData2 = include __DIR__ . '/data/success_cache.php';
        $this->assertEquals($cacheData2, $cacheData);
    }


    public function testInvalidLoadCacheFileNotReturnArray()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/not_array.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache file "%s"',
            $cacheFile
        ));
        $router->uriFor('foo');
    }

    public function testInvalidLoadCacheFileNotIssetRoutes()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/not_isset_routes.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache file "%s"',
            $cacheFile
        ));
        $router->uriFor('foo');
    }

    public function testInvalidLoadCacheFileRoutesNotArray()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/not_is_array_routes.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache file "%s"',
            $cacheFile
        ));
        $router->uriFor('foo');
    }

    public function testInvalidLoadCacheFileIssetDispatchData()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/not_isset_dispatch_data.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache file "%s"',
            $cacheFile
        ));
        $router->uriFor('foo');
    }

    public function testInvalidLoadCacheFileNotArrayDispatchData()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/not_is_array_dispatch_data.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid cache file "%s"',
            $cacheFile
        ));
        $router->uriFor('foo');
    }

    public function testSuccessLoadFromCache()
    {
        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $cacheFile = __DIR__ . '/data/success_cache.php';
        $router = new Router($routeDefinitionCallback, true, $cacheFile);
        $router->uriFor('foo');

        $class = new \ReflectionClass($router);
        $property = $class->getProperty('routes');
        $property->setAccessible(true);
        $property2 = $class->getProperty('dispatchData');
        $property2->setAccessible(true);

        $cacheData = include __DIR__ . '/data/success_cache.php';
        $this->assertEquals($cacheData['routes'], $property->getValue($router));
        $this->assertEquals($cacheData['dispatch_data'], $property2->getValue($router));
    }
}
