<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Router\Tests;

use Berlioz\Http\Message\Request;
use Berlioz\Router\Attribute;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route;
use Exception;

class RouteTest extends AbstractTestCase
{
    public function testConstructorWithOnlyFirstParameter()
    {
        $route = new Route($path = '/my-path/{foo}/{bar}');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($path, $route->getPath());
    }

    public function testConstructor()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            ['foo' => 'value', 'bar' => false, 'baz' => 'default'],
            ['foo' => '\d+', 'bar' => null],
            'my-route',
            [Request::HTTP_METHOD_GET],
            ['getberlioz.com'],
            100,
            $options = ['option' => true],
            $context = ['controller' => 'TestTestController'],
        );

        $this->assertInstanceOf(Route::class, $route);

        $this->assertInstanceOf(Attribute::class, $route->getAttribute('foo'));
        $this->assertEquals('value', $route->getAttribute('foo')->getDefault());
        $this->assertEquals('\d+', $route->getAttribute('foo')->getRegex());

        $this->assertInstanceOf(Attribute::class, $route->getAttribute('bar'));
        $this->assertEquals(false, $route->getAttribute('bar')->getDefault());
        $this->assertNull($route->getAttribute('bar')->getRegex());

        $this->assertInstanceOf(Attribute::class, $route->getAttribute('baz'));
        $this->assertEquals('default', $route->getAttribute('baz')->getDefault());
        $this->assertNull($route->getAttribute('baz')->getRegex());

        $this->assertEquals('my-route', $route->getName());
        $this->assertEquals([Request::HTTP_METHOD_GET], $route->getMethods());
        $this->assertEquals(['getberlioz.com'], $route->getHosts());
        $this->assertEquals(100, $route->getPriority());

        $this->assertEquals($options, $route->getOptions());
        $this->assertEquals(true, $route->getOption('option'));
        $this->assertNull($route->getOption('unknown'));

        $this->assertEquals($context, $route->getContext());
    }

    public function testConstructor_withoutParameters()
    {
        $route = new Route();

        $this->assertInstanceOf(Route::class, $route);
    }

    public function testSerialization()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            ['foo' => 'value', 'bar' => false, 'baz' => 'default'],
            ['foo' => '\d+', 'bar' => null],
            'my-route',
            [Request::HTTP_METHOD_GET],
            'getberlioz.com',
            100,
            $options = ['option' => true],
            $context = ['controller' => 'TestTestController'],
        );

        $serialized = serialize($route);
        $unserialized = unserialize($serialized);

        $this->assertEquals($route, $unserialized);
    }

    public function testGetName()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            name: 'my-route',
        );
        $this->assertEquals('my-route', $route->getName());
    }

    public function testGetOptions()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            options: $options = ['option' => true],
        );

        $this->assertEquals($options, $route->getOptions());
    }

    public function testGetOption()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            options: ['option' => true],
        );

        $this->assertEquals(true, $route->getOption('option'));
        $this->assertEquals(false, $route->getOption('unknown', false));
        $this->assertNull($route->getOption('unknown'));
    }

    public function testGetContext()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            context: ['controller' => 'TestTestController'],
        );
        $this->assertEquals(
            ['controller' => 'TestTestController'],
            $route->getContext()
        );
    }

    public function testSetContext()
    {
        $route = new Route('/my-path/{foo}/{bar}');
        $context = [
            'controller' => 'Test2Test2Controller',
            'function' => 'MyFunction',
        ];

        $route->setContext($context);
        $this->assertEquals($context, $route->getContext());
    }

    public function testGetMethods()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            method: [
                Request::HTTP_METHOD_POST,
                Request::HTTP_METHOD_GET,
                Request::HTTP_METHOD_PUT,
            ]
        );

        $this->assertEquals(
            [
                Request::HTTP_METHOD_POST,
                Request::HTTP_METHOD_GET,
                Request::HTTP_METHOD_PUT,
            ],
            $route->getMethods()
        );
    }

    public function testGetMethodsWithOneMethod()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            method: Request::HTTP_METHOD_GET
        );

        $this->assertEquals([Request::HTTP_METHOD_GET], $route->getMethods());
    }

    public function testGetMethodsDefault()
    {
        $route = new Route('/my-path/{foo}/{bar}');

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
        $route = new Route('/my-path/{foo}/{bar}');

        $this->assertEquals('/my-path/{foo}/{bar}', $route->getPath());
    }

    public function testTestRoute()
    {
        $route = new Route('/my-path/{foo}/{bar}');

        $this->assertTrue($route->test($this->getServerRequest('/my-path/1value1/value2')));
        $this->assertFalse($route->test($this->getServerRequest('/my-path/1va/lue1/value2')));
    }

    public function testTestRouteWithRequirements()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            requirements: [
                'foo' => '\d+',
                'bar' => '.+',
            ],
        );
        $this->assertTrue($route->test($this->getServerRequest('/my-path/123/value2')));
        $this->assertFalse($route->test($this->getServerRequest('/my-path/12-3/value2')));
        $this->assertTrue($route->test($this->getServerRequest('/my-path/123/valu/e2')));

        $route = new Route(
            '/my-path/{foo}/{bar}',
            requirements: [
                'foo' => '\d+',
                'bar' => '.*',
            ],
        );
        $this->assertTrue($route->test($this->getServerRequest('/my-path/123/')));
        $this->assertTrue($route->test($this->getServerRequest('/my-path/123/value2')));
    }

    public function testTestRouteWithRequirementsInPath()
    {
        $route = new Route('/my-path/{foo::int}/{bar}');
        $route2 = new Route('/my-path/{foo:\d+}/{bar}');

        $this->assertTrue($route->test($this->getServerRequest('/my-path/123/value2')));
        $this->assertTrue($route2->test($this->getServerRequest('/my-path/123/value2')));
        $this->assertFalse($route->test($this->getServerRequest('/my-path/12-3/value2')));
        $this->assertFalse($route2->test($this->getServerRequest('/my-path/12-3/value2')));
    }

    public function testTestRouteWithRequirementsInPath_deprecated()
    {
        set_error_handler(
            function (int $errno, string $errstr) {
                throw new Exception($errstr, $errno);
            },
            E_USER_DEPRECATED
        );

        $this->expectExceptionCode(E_USER_DEPRECATED);

        $route = new Route('/my-path/{foo::uuid}');
        $this->assertTrue($route->test($this->getServerRequest('/my-path/8bd71855-5e84-4a0e-9595-98a5f180840d')));

        restore_error_handler();
    }

    public function testTestWithAttributes()
    {
        $route = new Route('/my-path/{foo}/{bar}');
        $attributes = [];
        $route->test($this->getServerRequest('/my-path/value1/value2'), $attributes);

        $this->assertEquals(
            [
                'foo' => 'value1',
                'bar' => 'value2',
            ],
            $attributes
        );
    }

    public function testTestWithOptionalPart()
    {
        $route = new Route('/my-path/{foo}[/{bar}]');

        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1')));
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1/value2')));
    }

    public function testTestWithOptionalPartWith2Attributes()
    {
        $route = new Route('/my-path/{foo}[/{bar}/{baz}]');

        $attributes = [];
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1'), $attributes));
        $this->assertEquals(
            [
                'foo' => 'value1',
            ],
            $attributes
        );

        $this->assertFalse($route->test($this->getServerRequest('/my-path/value1/value2')));

        $attributes = [];
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1/value2/value3'), $attributes));
        $this->assertEquals(
            [
                'foo' => 'value1',
                'bar' => 'value2',
                'baz' => 'value3',
            ],
            $attributes
        );
    }

    public function testTestWithNestedOptionalPart()
    {
        $route = new Route('/my-path/{foo}[[/{bar}]/sub-path/{baz}]');

        $attributes = [];
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1'), $attributes));
        $this->assertEquals(
            [
                'foo' => 'value1',
            ],
            $attributes
        );

        $attributes = [];
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1/sub-path/value3'), $attributes));
        $this->assertEquals(
            [
                'foo' => 'value1',
                'baz' => 'value3',
            ],
            $attributes
        );

        $attributes = [];
        $this->assertTrue($route->test($this->getServerRequest('/my-path/value1/value2/sub-path/value3'), $attributes));
        $this->assertEquals(
            [
                'foo' => 'value1',
                'bar' => 'value2',
                'baz' => 'value3',
            ],
            $attributes
        );
    }

    public function testTestForParentRoute()
    {
        $parentRoute = new Route(
            '/path/{foo}',
            requirements: ['bar' => '\d+']
        );
        $parentRoute->addRoute($route = new Route('/sub-path/{bar}', defaults: ['foo' => 'value']));

        $this->assertFalse($parentRoute->test($this->getServerRequest('/path/value/sub-path/123')));
        $this->assertTrue($route->test($this->getServerRequest('/path/value/sub-path/123')));
    }

    public function testGenerate()
    {
        $route = new Route('/my-path/{foo}/{bar}');
        $this->assertEquals(
            '/my-path/value1/value2',
            $route->generate(['foo' => 'value1', 'bar' => 'value2'])
        );
        $this->assertTrue(
            $route->test($this->getServerRequest($route->generate(['foo' => 'value1', 'bar' => 'value2'])))
        );

        $this->assertEquals(
            '/my-path/foo/bar/value2',
            $route->generate(['foo' => 'foo/bar', 'bar' => 'value2'])
        );
        $this->assertFalse(
            $route->test($this->getServerRequest($route->generate(['foo' => 'foo/bar', 'bar' => 'value2'])))
        );

        $this->assertEquals(
            '/my-path/foo%2Fbar/value2',
            $route->generate(['foo' => urlencode('foo/bar'), 'bar' => 'value2'])
        );
        $this->assertTrue(
            $route->test($this->getServerRequest($route->generate(['foo' => urlencode('foo/bar'), 'bar' => 'value2'])))
        );
    }

    public function testGenerateWithDefaultAttribute()
    {
        $route = new Route('/my-path/{foo}/{bar}', defaults: ['foo' => 'value1']);
        $this->assertEquals(
            '/my-path/value1/value2',
            $route->generate(['bar' => 'value2'])
        );
    }

    public function testGenerateWithMissingAttribute()
    {
        $this->expectException(RoutingException::class);

        $route = new Route('/my-path/{foo}/{bar}', defaults: ['foo' => 'value1']);
        $this->assertEquals(
            '/my-path/value1/value2',
            $route->generate()
        );
    }

    public function testGenerateWithMultidimensionalParameters()
    {
        $route = new Route(
            '/my-path/{foo}/{bar}',
            defaults: ['foo' => 'bar'],
            name: 'my-route',
            method: 'get'
        );
        $parameters = [
            'baz' => ['bar', 'baz', '', null, 0],
            'qux' => '',
            'quxx' => null,
            'foo' => 'value1',
            'bar' => 'value2'
        ];

        $this->assertEquals(
            '/my-path/value1/value2?baz%5B0%5D=bar&baz%5B1%5D=baz&baz%5B2%5D=&baz%5B4%5D=0&qux=',
            $route->generate($parameters)
        );
        $this->assertTrue($route->test($this->getServerRequest($route->generate($parameters))));
    }

    public function testGenerateWithParentRoute()
    {
        $parentRoute = new Route(
            '/path/{foo}',
            requirements: ['bar' => '\d+']
        );
        $parentRoute->addRoute($route = new Route('/sub-path/{bar}', defaults: ['foo' => 'value']));

        $this->assertEquals('/path/value/sub-path/123', $route->generate(['bar' => '123']));
    }

    public function testGenerateWithParentRouteAndEmptyPath()
    {
        $parentRoute = new Route(requirements: ['bar' => '\d+']);
        $parentRoute->addRoute($route = new Route('/sub-path/{bar}'));

        $this->assertEquals('/sub-path/123', $route->generate(['bar' => '123']));
    }

    public function testGenerateWithOptionalPart()
    {
        $route = new Route('/my-path/{foo}[/{bar}]');

        $this->assertEquals('/my-path/value1', $route->generate(['foo' => 'value1']));
        $this->assertEquals('/my-path/value1', $route->generate(['foo' => 'value1', 'bar' => null]));
        $this->assertEquals('/my-path/value1/value2', $route->generate(['foo' => 'value1', 'bar' => 'value2']));
    }

    public function testGenerateWithOptionalPartWith2Attributes()
    {
        $route = new Route('/my-path/{foo}[/{bar}/{baz}]');

        $this->assertEquals('/my-path/value1', $route->generate(['foo' => 'value1']));
        $this->assertEquals('/my-path/value1', $route->generate(['foo' => 'value1', 'bar' => 'value2']));
        $this->assertEquals(
            '/my-path/value1/value2/value3',
            $route->generate(['foo' => 'value1', 'bar' => 'value2', 'baz' => 'value3'])
        );
    }

    public function testGenerateWithNestedOptionalPart()
    {
        $route = new Route('/my-path/{foo}[[/{bar}]/sub-path/{baz}]');

        $this->assertEquals('/my-path/value1', $route->generate(['foo' => 'value1']));
        $this->assertEquals(
            '/my-path/value1/sub-path/value3',
            $route->generate(['foo' => 'value1', 'baz' => 'value3'])
        );
        $this->assertEquals(
            '/my-path/value1/value2/sub-path/value3',
            $route->generate(['foo' => 'value1', 'bar' => 'value2', 'baz' => 'value3'])
        );
    }

    public function testGenerateWithNestedOptionalPartAndDefaultValue()
    {
        $route = new Route('/my-path/{foo}[[/{bar}]/sub-path/{baz}]', defaults: ['baz' => 'default']);

        $this->assertEquals('/my-path/value1/sub-path/default', $route->generate(['foo' => 'value1']));
    }

    public function testGetRoutes()
    {
        $parentRoute = new Route(
            '/path/{foo}',
            requirements: ['bar' => '\d+']
        );
        $parentRoute->addRoute(new Route('/sub-path/{bar}', defaults: ['foo' => 'value']));
        $parentRoute2 = new Route('/path2/{foo}');
        $parentRoute2->addRoute(new Route('/sub-path2/{bar}', defaults: ['foo' => 'value']));
        $parentRoute->addRoute($parentRoute2);

        $routes = iterator_to_array($parentRoute->getRoutes(), false);
        $this->assertCount(2, $routes);
    }
}
