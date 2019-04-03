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

declare(strict_types=1);

namespace Berlioz\Router;

use Psr\Http\Message\UriInterface;

/**
 * Class RouteSet.
 *
 * @package Berlioz\Router
 * @see     \Berlioz\Router\RouteSetInterface
 */
class RouteSet implements RouteSetInterface, \Serializable
{
    /** @var \Berlioz\Router\RouteInterface[] */
    private $routes;

    /**
     * RouteSet constructor.
     */
    public function __construct()
    {
        $this->routes = [];
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize(['routes' => $this->routes]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $tmpUnserialized = unserialize($serialized);

        $this->routes = $tmpUnserialized['routes'];
    }

    /**
     * @inheritdoc
     */
    public function addRoute(RouteInterface $route): RouteSetInterface
    {
        $this->routes[$route->getName()] = $route;
        $this->orderRoutes();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Order routes by priority and number of parameters (DESC).
     */
    private function orderRoutes()
    {
        uasort(
            $this->routes,
            function (RouteInterface $a, RouteInterface $b) {
                if (($a->getOptions()['priority'] ?? -1) == ($b->getOptions()['priority'] ?? -1)) {
                    if ($a->getNumberOfParameters() == $b->getNumberOfParameters()) {
                        return 0;
                    } else {
                        return ($a->getNumberOfParameters() < $b->getNumberOfParameters()) ? -1 : 1;
                    }
                } else {
                    return ($a->getOptions()['priority'] ?? -1) > ($b->getOptions()['priority'] ?? -1) ? -1 : 1;
                }
            });
    }

    /**
     * @inheritdoc
     */
    public function merge(RouteSetInterface $routeSet): RouteSetInterface
    {
        $this->routes = array_merge($this->routes, $routeSet->getRoutes());
        $this->orderRoutes();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getByName($name): ?RouteInterface
    {
        return $this->routes[$name] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function searchRoute(UriInterface $uri, string $method = null): ?RouteInterface
    {
        $httpPath = urldecode($uri->getPath());

        if (!empty($method)) {
            $method = mb_strtoupper($method);
        }

        foreach ($this->routes as $route) {
            if (empty($method) || in_array($method, $route->getMethods())) {
                if ($route->test($httpPath)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Count routes.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->routes);
    }
}