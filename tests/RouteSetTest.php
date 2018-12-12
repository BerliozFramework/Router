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

use Berlioz\Http\Message\Uri;
use PHPUnit\Framework\TestCase;

class RouteSetTest extends TestCase
{
    public function testConstruct()
    {
        $routeSet = new RouteSet;
        $this->assertInstanceOf(RouteSet::class, $routeSet);
    }

    public function testSerialization()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path'));

        $serialized = serialize($routeSet);
        $unserialized = unserialize($serialized);

        $this->assertEquals($routeSet, $unserialized);
    }

    public function testAddRoute()
    {
        $routeSet = new RouteSet;
        $route = new Route('/path');
        $routeSet->addRoute($route);

        $this->assertContains($route, $routeSet->getRoutes());

        $route2 = new Route('/path2', ['name' => 'test']);
        $routeSet->addRoute($route2);

        $this->assertContains($route, $routeSet->getRoutes());
        $this->assertContains($route2, $routeSet->getRoutes());

        $route3 = new Route('/path3', ['name' => 'test']);
        $routeSet->addRoute($route3);

        $this->assertContains($route, $routeSet->getRoutes());
        $this->assertContains($route3, $routeSet->getRoutes());
        $this->assertNotContains($route2, $routeSet->getRoutes());
    }

    public function testGetRoutes()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute($route = new Route('/path'));
        $routeSet->addRoute(new Route('/path2', ['name' => 'test']));
        $routeSet->addRoute($route2 = new Route('/path3', ['name' => 'test']));

        $this->assertEquals(2, count($routeSet->getRoutes()));
        $this->assertContains($route, $routeSet->getRoutes());
        $this->assertContains($route2, $routeSet->getRoutes());
    }

    public function testMerge()
    {
        $routeSet1 = new RouteSet;
        $routeSet1->addRoute($route1 = new Route('/path'));
        $routeSet1->addRoute($route2 = new Route('/path2', ['name' => 'test']));
        $routeSet1->addRoute($route3 = new Route('/path3', ['name' => 'test']));

        $routeSet2 = new RouteSet;
        $routeSet2->addRoute($route4 = new Route('/path4'));
        $routeSet2->addRoute($route5 = new Route('/path5', ['name' => 'test2']));
        $routeSet2->addRoute($route6 = new Route('/path6', ['name' => 'test']));

        $routeSet1->merge($routeSet2);

        $this->assertEquals(4, count($routeSet1->getRoutes()));
        $this->assertContains($route1, $routeSet1->getRoutes());
        $this->assertNotContains($route2, $routeSet1->getRoutes());
        $this->assertNotContains($route3, $routeSet1->getRoutes());
        $this->assertContains($route4, $routeSet1->getRoutes());
        $this->assertContains($route5, $routeSet1->getRoutes());
        $this->assertContains($route6, $routeSet1->getRoutes());
    }

    public function testGetByName()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute($route1 = new Route('/path', ['name' => 'test1']));
        $routeSet->addRoute($route2 = new Route('/path2', ['name' => 'test2']));
        $routeSet->addRoute($route3 = new Route('/path3', ['name' => 'test3']));
        $routeSet->addRoute($route4 = new Route('/path4', ['name' => 'test4']));

        $this->assertEquals($route4, $routeSet->getByName('test4'));
    }

    public function testSearchRoute()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute($route1 = new Route('/path/{attr1}/{attr2}',
                                                ['name' => 'route1']));
        $routeSet->addRoute($route2 = new Route('/path/{attr1}/{attr2}',
                                                ['name'         => 'route2',
                                                 'requirements' => ['attr2' => '\d+'],
                                                 'priority'     => 1]));
        $routeSet->addRoute($route3 = new Route('/path/{attr1}/test',
                                                ['name'         => 'route3',
                                                 'requirements' => ['attr1' => '\d+'],
                                                 'priority'     => 1]));
        $routeSet->addRoute($route4 = new Route('/path2/{attr1}/test',
                                                ['name'         => 'route4',
                                                 'requirements' => ['attr1' => '\d+']]));
        $routeSet->addRoute($route5 = new Route('/path2/{attr1}/test',
                                                ['name'         => 'route5',
                                                 'requirements' => ['attr1' => 'element\d+']]));
        $routeSet->addRoute($route6 = new Route('/second-path/{attr}/new',
                                                ['name'         => 'route6',
                                                 'requirements' => ['attr' => '[a-f0-9]{8}']]));
        $routeSet->addRoute($route7 = new Route('/third-path/{attr}/new',
                                                ['name'         => 'route7',
                                                 'requirements' => ['attr' => '.+'],
                                                 'defaults'     => ['attr' => 'all']]));

        $uri = Uri::createFromString('https://www.phpunit.com/path/testAttr/testAttr2');
        $this->assertEquals($route1, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/path/testAttr/2');
        $this->assertEquals($route2, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/path/1/test');
        $this->assertEquals($route3, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/path2/1/test');
        $this->assertEquals($route4, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/path2/test1/test');
        $this->assertEquals(null, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/second-path/1/test');
        $this->assertEquals(null, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/second-path/234a/new');
        $this->assertEquals(null, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/second-path/a2efb43d/new');
        $this->assertEquals($route6, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/third-path/test/new');
        $this->assertEquals($route7, $routeSet->searchRoute($uri));

        $uri = Uri::createFromString('https://www.phpunit.com/third-path/test/test/test/new');
        $this->assertEquals($route7, $routeSet->searchRoute($uri));
    }

    public function testCount()
    {
        $routeSet = new RouteSet;
        $routeSet->addRoute(new Route('/path', ['name' => 'test1']));
        $routeSet->addRoute(new Route('/path2', ['name' => 'test2']));
        $routeSet->addRoute(new Route('/path3', ['name' => 'test1']));
        $routeSet->addRoute(new Route('/path4', ['name' => 'test4']));

        $this->assertEquals(3, count($routeSet));
    }
}
