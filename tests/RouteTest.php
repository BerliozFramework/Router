<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Router\Tests;

use Berlioz\Http\Message\Request;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    private function getValidRoute()
    {
        return new Route(
            '/my-path/{test}/{test2}',
            [
                'method' => 'get',
                'name' => 'my-route',
            ],
            ['controller' => 'TestTestController']
        );
    }

    public function testConstructor()
    {
        $route = new Route('/my-path/{test}/{test2}');
        $this->assertInstanceOf(Route::class, $route);

        $route = new Route(
            '/my-path/{test}/{test2}',
            ['option1' => 'test', 'option2' => false],
            ['controller' => 'TestTestController']
        );
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('test', $route->getOptions()['option1']);
        $this->assertEquals('TestTestController', $route->getContext()['controller']);
    }

    public function testSerialization()
    {
        $route = new Route(
            '/my-path/{test}/{test2}',
            ['option1' => 'test', 'option2' => false],
            ['controller' => 'TestTestController']
        );

        $serialized = serialize($route);
        $unserialized = unserialize($serialized);

        $this->assertEquals($route, $unserialized);
    }

    public function testGetName()
    {
        $route = $this->getValidRoute();
        $this->assertEquals('my-route', $route->getName());
    }

    public function testGetOptions()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(
            [
                'method' => 'get',
                'name' => 'my-route',
            ],
            $route->getOptions()
        );
    }

    public function testGetContext()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(
            ['controller' => 'TestTestController'],
            $route->getContext()
        );
    }

    public function testSetContext()
    {
        $route = $this->getValidRoute();
        $context = [
            'controller' => 'Test2Test2Controller',
            'function' => 'MyFunction',
        ];

        $route->setContext($context);
        $this->assertEquals($context, $route->getContext());
    }

    public function testGetMethods()
    {
        // One method
        $route = $this->getValidRoute();
        $this->assertEquals([Request::HTTP_METHOD_GET], $route->getMethods());

        // Multiple methods
        $route = new Route('/path', ['method' => 'post, get, put']);
        $this->assertEquals(
            [
                Request::HTTP_METHOD_POST,
                Request::HTTP_METHOD_GET,
                Request::HTTP_METHOD_PUT,
            ],
            $route->getMethods()
        );

        // Default methods
        $route = new Route('/path');
        $this->assertEquals(
            [
                Request::HTTP_METHOD_GET,
                Request::HTTP_METHOD_HEAD,
                Request::HTTP_METHOD_POST,
                Request::HTTP_METHOD_OPTIONS,
                Request::HTTP_METHOD_CONNECT,
                Request::HTTP_METHOD_TRACE,
                Request::HTTP_METHOD_PUT,
                Request::HTTP_METHOD_DELETE,
            ],
            $route->getMethods()
        );
    }

    public function testGetRoute()
    {
        $route = $this->getValidRoute();
        $this->assertEquals('/my-path/{test}/{test2}', $route->getRoute());
    }

    public function testTest()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(true, $route->test('/my-path/1value1/value2'));
        $route = $this->getValidRoute();
        $this->assertEquals(false, $route->test('/my-path/1va/lue1/value2'));

        // With requirements
        $route = new Route(
            '/my-path/{test}/{test2}',
            [
                'requirements' => [
                    'test' => '\d+',
                    'test2' => '.+',
                ],
            ]
        );
        $this->assertEquals(true, $route->test('/my-path/123/value2'));
        $this->assertEquals(false, $route->test('/my-path/12-3/value2'));
        $this->assertEquals(true, $route->test('/my-path/123/valu/e2'));
        $route = new Route(
            '/my-path/{test}/{test2}',
            [
                'requirements' => [
                    'test' => '\d+',
                    'test2' => '.*',
                ],
            ]
        );
        $this->assertEquals(true, $route->test('/my-path/123/'));
        $this->assertEquals(true, $route->test('/my-path/123/value2'));
    }

    public function testGenerate()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(
            '/my-path/value1/value2',
            $route->generate(['test' => 'value1', 'test2' => 'value2'])
        );
        $this->assertTrue($route->test($route->generate(['test' => 'value1', 'test2' => 'value2'])));

        $this->assertEquals(
            '/my-path/foo/bar/value2',
            $route->generate(['test' => 'foo/bar', 'test2' => 'value2'])
        );
        $this->assertFalse($route->test($route->generate(['test' => 'foo/bar', 'test2' => 'value2'])));

        $this->assertEquals(
            '/my-path/foo%2Fbar/value2',
            $route->generate(['test' => urlencode('foo/bar'), 'test2' => 'value2'])
        );
        $this->assertTrue($route->test($route->generate(['test' => urlencode('foo/bar'), 'test2' => 'value2'])));
    }

    public function testExtractAttributes()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(
            [
                'test' => 'value1',
                'test2' => 'value2',
            ],
            $route->extractAttributes('/my-path/value1/value2')
        );
    }

    public function testExtractAttributesException()
    {
        $route = $this->getValidRoute();
        $this->expectException(RoutingException::class);
        $route->extractAttributes('/my-path/value1');
    }

    public function testGetNumberOfParameters()
    {
        $route = $this->getValidRoute();
        $this->assertEquals(2, $route->getNumberOfParameters());

        $route = new Route('/my-path/value1/value2');
        $this->assertEquals(0, $route->getNumberOfParameters());
    }

    public function testRouteWithDuplicateAttributes()
    {
        $this->expectException(RoutingException::class);
        new Route('/my-path/{test}/{test2}/{test}');
    }
}
