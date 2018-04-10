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

use Berlioz\Router\Exception\RoutingException;
use PHPUnit\Framework\TestCase;

class RouteGeneratorTest extends TestCase
{
    public function testParseClass()
    {
        $routeGenerator = new RouteGenerator;
        $routeSet = $routeGenerator->parseClass('\Berlioz\Router\Tests\Includes\ControllerTest');

        $this->assertEquals(2, count($routeSet));

        $this->assertInstanceOf(RouteInterface::class, $route = $routeSet->getByName('method1'));
        $this->assertEquals('method1', $route->getName());
    }

    public function testAddUnknownClass()
    {
        $this->expectException(RoutingException::class);
        $routeGenerator = new RouteGenerator;
        $routeGenerator->parseClass('\Berlioz\Router\Tests\Includes\ControllerTestTest');
    }

    public function testContext()
    {
        $routeGenerator = new RouteGenerator;
        $routeSet = $routeGenerator->parseClass('\Berlioz\Router\Tests\Includes\ControllerTest', '', ['aContext' => 'value']);
        $this->assertInstanceOf(RouteInterface::class, $route = $routeSet->getByName('method1'));
        $this->assertEquals(['aContext' => 'value',
                             '_class'   => 'Berlioz\Router\Tests\Includes\ControllerTest',
                             '_method'  => 'methodTest1'],
                            $route->getContext());
    }
}
