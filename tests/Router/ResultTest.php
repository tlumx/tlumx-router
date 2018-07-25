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

use Tlumx\Router\Result;

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateSuccess()
    {
        $result = Result::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'home', 'action' => 'index']
        );
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertEquals('my-route', $result->getRouteName());
        $this->assertEquals(['a' => 10, 'b' => 'str1'], $result->getParams());
        $this->assertEquals(['GET', 'POST'], $result->getAllowedMethods());
        $this->assertEquals(['midd1', 'midd2'], $result->getRouteMiddlewares());
        $this->assertEquals(['controller' => 'home', 'action' => 'index'], $result->getRouteHandler());
    }

    public function testCreateNotFound()
    {
        $result = Result::createNotFound();
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertNull($result->getRouteName());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals([], $result->getAllowedMethods());
        $this->assertEquals([], $result->getRouteMiddlewares());
        $this->assertEquals([], $result->getRouteHandler());
    }

    public function testCreateMethodNotAllowed()
    {
        $result = Result::createMethodNotAllowed(['GET', 'POST']);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isNotFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertNull($result->getRouteName());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals(['GET', 'POST'], $result->getAllowedMethods());
        $this->assertEquals([], $result->getRouteMiddlewares());
        $this->assertEquals([], $result->getRouteHandler());
    }
}
