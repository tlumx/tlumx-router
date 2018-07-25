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

use Tlumx\Router\RouteCollector;
use FastRoute\RouteParser\Std as StdParser;
use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;

class RouteCollectorTest extends \PHPUnit\Framework\TestCase
{

    public function testRoutesCollection()
    {
        $r = new RouteCollector();

        $routesData = $r->generateRoutesData(new StdParser, new DataGeneratorGroupCountBased);
        $this->assertEquals(['routes' => [],'dispatch_data' => [0 => [],1 => []]], $routesData);

        $r->addRoute(
            'my',
            ['GET','POST'],
            '/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]',
            ['midd1', 'midd2'],
            ['controller' => 'home', 'action' => 'index'],
            'admin'
        );
        $r->addGroup('admin', '/admin', ['addmin_midd1', 'addmin_midd2']);

        $routesData = $r->generateRoutesData(new StdParser, new DataGeneratorGroupCountBased);
        $this->assertEquals(
            [
                'routes' => [
                    'my' => [
                        'datas' => [
                            [
                                "/admin/fixedRoutePart/",
                                ["varName", "[^/]+"],
                            ],
                            [
                                "/admin/fixedRoutePart/",
                                ["varName", "[^/]+"],
                                "/moreFixed/",
                                ["varName2", "\d+"],
                            ]
                        ],
                        'methods' => ['GET', 'POST'],
                        'pattern' => '/admin/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]',
                        'middlewares' => ['addmin_midd1', 'addmin_midd2', 'midd1', 'midd2'],
                        'handler' => ['controller' => 'home', 'action' => 'index'],
                        'group' => 'admin'
                    ]
                ],
                'dispatch_data' => [
                    [],
                    [
                        'GET' => [
                            [
                                'regex' => '~^(?|/admin/fixedRoutePart/([^/]+)|/admin/'.
                                    'fixedRoutePart/([^/]+)/moreFixed/(\d+))$~',
                                'routeMap' => [
                                    2 => [
                                        'my',
                                        ['varName' => 'varName']
                                    ],
                                    3 => [
                                        'my',
                                        [
                                            'varName' => 'varName', 'varName2' => 'varName2'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'POST' => [
                            [
                                'regex' => '~^(?|/admin/fixedRoutePart/([^/]+)|/admin/'.
                                    'fixedRoutePart/([^/]+)/moreFixed/(\d+))$~',
                                'routeMap' => [
                                    2 => [
                                        'my',
                                        ['varName' => 'varName']
                                    ],
                                    3 => [
                                        'my',
                                        [
                                            'varName' => 'varName', 'varName2' => 'varName2'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $routesData
        );
    }

    public function testInvalidRoutesNoIssetRouteGroup()
    {
        $routeGroup = 'admin';

        $r = new RouteCollector();
        $r->addRoute(
            'my',
            ['GET','POST'],
            '/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]',
            ['midd1', 'midd2'],
            ['controller' => 'home', 'action' => 'index'],
            $routeGroup
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The route group "%s" is not found',
            $routeGroup
        ));

        $routesData = $r->generateRoutesData(new StdParser, new DataGeneratorGroupCountBased);
    }
}
