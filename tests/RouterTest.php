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

namespace Berlioz\Router;

use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{
    private function getServerRequest()
    {
        return new ServerRequest('GET',
                                 Uri::createFromString('https://www.phpunit.com/path/test/sub-path'),
                                 [],
                                 [],
                                 [],
                                 new Stream);
    }

    public function testSerialization()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path'));
        $router = new Router;
        $router->setRouteSet($routeSet);
        $router->setServerRequest($this->getServerRequest());

        $serialized = serialize($router);
        $unserialized = unserialize($serialized);

        $this->assertNotEquals($router, $unserialized);
        $this->assertEquals($router->getRouteSet(), $unserialized->getRouteSet());
    }

    public function testGetSetRouteSet()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path'));
        $router = new Router;
        $router->setRouteSet($routeSet);

        $this->assertEquals($routeSet, $router->getRouteSet());
    }

    public function testGetServerRequest()
    {
        $router = new Router;
        $this->assertInstanceOf(ServerRequestInterface::class, $router->getServerRequest());
    }

    public function testSetServerRequest()
    {
        $router = new Router;
        $serverRequest = $this->getServerRequest();
        $router->setServerRequest($serverRequest);

        $this->assertEquals($serverRequest, $router->getServerRequest());
    }

    public function testGenerate()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path/{attr1}/sub-path', ['name' => 'route1']));
        $routeSet->addRoute(new Route('/path/{attr1}/sub-path/{attr2}',
                                      ['name'     => 'route2',
                                       'defaults' => ['attr2' => 'default']]));
        $router = new Router;
        $router->setRouteSet($routeSet);

        $this->assertEquals('/path/test/sub-path',
                            $router->generate('route1',
                                              ['attr1' => 'test']));
        $this->assertEquals('/path/test/sub-path/test2',
                            $router->generate('route2',
                                              ['attr1' => 'test',
                                               'attr2' => 'test2']));
        $this->assertEquals('/path/test/sub-path/default',
                            $router->generate('route2',
                                              ['attr1' => 'test']));
        $this->assertEquals('/path/test/sub-path?querystring1=value1&querystring2=value1',
                            $router->generate('route1',
                                              ['attr1'        => 'test',
                                               'querystring1' => 'value1',
                                               'querystring2' => 'value1']));
        $this->assertEquals('/path/test/sub-path/test2',
                            $router->generate('route2',
                                              ['attr1' => 'test',
                                               'attr2' => 'test2']));
        $this->assertEquals(false, $router->generate('route2', ['attr2' => 'test2']));
    }

    public function testIsValid()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path/{attr1}/sub-path', ['name' => 'route1']));
        $routeSet->addRoute(new Route('/path/{attr1}/sub-path/{attr2}',
                                      ['name'         => 'route2',
                                       'method'       => 'post',
                                       'requirements' => ['attr1' => '\d+'],
                                       'defaults'     => ['attr2' => 'default']]));
        $router = new Router;
        $router->setRouteSet($routeSet);

        $this->assertEquals(true, $router->isValid('/path/test/sub-path?querystring1=value1&querystring2=value1'));
        $this->assertEquals(false, $router->isValid('/path/test/sub-path/test?querystring1=value1&querystring2=value1'));
        $this->assertEquals(false, $router->isValid('/path/123/sub-path/test?querystring1=value1&querystring2=value1'));
        $this->assertEquals(true, $router->isValid('/path/123/sub-path/test?querystring1=value1&querystring2=value1', Request::HTTP_METHOD_POST));
        $this->assertEquals(false, $router->isValid('/unknown-path/test'));
    }

    public function testHandle()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute($route1 = new Route('/path/{attr1}/sub-path', ['name' => 'route1']));
        $routeSet->addRoute($route2 = new Route('/path/{attr1}/sub-path/{attr2}',
                                                ['name'         => 'route2',
                                                 'requirements' => ['attr1' => '\d+'],
                                                 'defaults'     => ['attr2' => 'default']]));
        $router = new Router;
        $router->setRouteSet($routeSet);
        $router->setServerRequest($this->getServerRequest());

        $this->assertEquals($route1, $router->handle());

        $serverRequest = $this->getServerRequest();
        $router->setServerRequest($serverRequest->withUri(Uri::createFromString('https://www.phpunit.com/path/test/sub-path/test')));

        $this->assertEquals(null, $router->handle());
    }
}
