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
use Berlioz\Http\Message\Uri;
use Berlioz\Router\Exception\NotFoundException;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route;
use Berlioz\Router\RouteAttributes;
use Berlioz\Router\Router;

class RouterTest extends AbstractTestCase
{
    public function testSerialization()
    {
        $router = new Router;
        $router->addRoute(new Route('/path'));

        $serialized = serialize($router);
        $unserialized = unserialize($serialized);

        $this->assertEquals($router, $unserialized);
        $this->assertEquals($router->getRoutes(), $unserialized->getRoutes());
    }

    public function testGetRoutes()
    {
        $router = new Router;
        $router->addRoute($route = new Route('/path'));

        $this->assertContains($route, $router->getRoutes());
    }

    public function testGenerate()
    {
        $router = new Router;
        $router->addRoute(new Route('/path/{attr1}/sub-path', name: 'route1'));
        $router->addRoute(
            new Route(
                '/path/{attr1}/sub-path/{attr2}',
                defaults: ['attr2' => 'default'],
                name: 'route2',
            )
        );

        $this->assertEquals(
            '/path/test/sub-path',
            $router->generate(
                'route1',
                ['attr1' => 'test']
            )
        );
        $this->assertEquals(
            '/path/test/sub-path/test2',
            $router->generate(
                'route2',
                [
                    'attr1' => 'test',
                    'attr2' => 'test2'
                ]
            )
        );
        $this->assertEquals(
            '/path/test/sub-path/default',
            $router->generate(
                'route2',
                ['attr1' => 'test']
            )
        );
        $this->assertEquals(
            '/path/test/sub-path?querystring1=value1&querystring2=value1',
            $router->generate(
                'route1',
                [
                    'attr1' => 'test',
                    'querystring1' => 'value1',
                    'querystring2' => 'value1'
                ]
            )
        );
        $this->assertEquals(
            '/path/test/sub-path/test2',
            $router->generate(
                'route2',
                [
                    'attr1' => 'test',
                    'attr2' => 'test2'
                ]
            )
        );
    }

    public function testGenerateWithMissingAttributes()
    {
        $router = new Router;
        $router->addRoute(
            new Route(
                '/path/{attr1}/sub-path/{attr2}',
                defaults: ['attr2' => 'default'],
                name: 'route2',
            )
        );

        $this->expectException(RoutingException::class);
        $router->generate('route2', ['attr2' => 'test2']);
    }

    public function testGenerateWithNotFoundRoute()
    {
        $router = new Router;
        $router->addRoute(new Route('/path/{attr1}/sub-path', name: 'route1'));

        $this->expectException(NotFoundException::class);
        $router->generate('route2', ['attr2' => 'test2']);
    }

    public function testGenerateWithRouteAttributes()
    {
        $router = new Router;
        $router->addRoute(new Route('/path/{user}/{attr}', name: 'route1'));
        $router->addRoute(new Route('/path/{user}/sub-path', name: 'route2'));

        $fakeRouteAttributes1 = new class implements RouteAttributes {
            public function routeAttributes(): array
            {
                return [
                    'user' => 1,
                    'attr' => 'foo',
                ];
            }
        };
        $fakeRouteAttributes2 = new class implements RouteAttributes {
            public function routeAttributes(): array
            {
                return [
                    'user' => 1,
                ];
            }
        };

        $this->assertEquals(
            '/path/1/foo',
            $router->generate('route1', $fakeRouteAttributes1)
        );
        $this->assertEquals(
            '/path/1/sub-path',
            $router->generate('route2', $fakeRouteAttributes2)
        );
        $this->assertEquals(
            '/path/1/bar',
            $router->generate('route1', [$fakeRouteAttributes2, 'attr' => 'bar'])
        );
        $this->assertEquals(
            '/path/1/sub-path?attr=bar',
            $router->generate('route2', [$fakeRouteAttributes2, ['attr' => 'bar']])
        );
    }

    public function testIsValid()
    {
        $router = new Router;
        $router->addRoute($route1 = new Route('/path/{attr1}/sub-path', name: 'route1'));
        $router->addRoute(
            new Route(
                '/path/{attr1}/sub-path/{attr2}',
                defaults: ['attr2' => 'default'],
                requirements: ['attr1' => '\d+'],
                name: 'route2',
                method: 'post',
            )
        );

        $this->assertTrue(
            $router->isValid($this->getServerRequest('/path/test/sub-path?querystring1=value1&querystring2=value1'))
        );
        $this->assertFalse(
            $router->isValid(
                $this->getServerRequest('/path/test/sub-path/test?querystring1=value1&querystring2=value1')
            )
        );
        $this->assertFalse(
            $router->isValid($this->getServerRequest('/path/123/sub-path/test?querystring1=value1&querystring2=value1'))
        );
        $this->assertTrue(
            $router->isValid(
                $this->getServerRequest(
                    '/path/123/sub-path/test?querystring1=value1&querystring2=value1',
                    Request::HTTP_METHOD_POST
                )
            )
        );
        $this->assertFalse($router->isValid($this->getServerRequest('/unknown-path/test')));
    }

    public function testIsValid_withString()
    {
        $router = new Router;
        $router->addRoute(new Route('/path/{attr1}/sub-path', name: 'route1'));
        $router->addRoute(
            new Route(
                '/path/{attr1}/sub-path/{attr2}',
                defaults: ['attr2' => 'default'],
                requirements: ['attr1' => '\d+'],
                name: 'route2',
                method: 'post',
            )
        );

        $this->assertTrue($router->isValid('/path/test/sub-path?querystring1=value1&querystring2=value1'));
        $this->assertFalse($router->isValid('/path/test/sub-path/test?querystring1=value1&querystring2=value1'));
        $this->assertFalse($router->isValid('/path/123/sub-path/test?querystring1=value1&querystring2=value1'));
        $this->assertFalse($router->isValid('/path/123/sub-path/test?querystring1=value1&querystring2=value1'));
        $this->assertFalse($router->isValid('/unknown-path/test'));
    }

    public function testHandle()
    {
        $router = new Router;
        $router->addRoute($route1 = new Route('/path/{attr1}/sub-path', name: 'route1'));
        $router->addRoute(
            $route2 = new Route(
                '/path/{attr1}/sub-path/{attr2}',
                defaults: ['attr2' => 'default'],
                requirements: ['attr1' => '\d+'],
                name: 'route2'
            )
        );

        $serverRequest = $this->getServerRequest();
        $this->assertEquals($route1, $router->handle($serverRequest));

        $serverRequest = $serverRequest->withUri(
            Uri::createFromString('https://www.phpunit.com/path/test/sub-path/test')
        );
        $this->assertNull($router->handle($serverRequest));
    }

    public function testHandlePriority()
    {
        $serverRequest = $this->getServerRequest();
        $router = new Router;
        $router->addRoute($route1 = new Route('/path/{attr1}/sub-path', name: 'route1'));
        $router->addRoute($route2 = new Route('/path/{attr1}/sub-path', name: 'route2', priority: 100));

        $this->assertEquals($route2, $router->handle($serverRequest));
    }
}
