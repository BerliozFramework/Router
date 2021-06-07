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

declare(strict_types=1);

namespace Berlioz\Router;

use Generator;
use Psr\Http\Message\ServerRequestInterface;

trait RouteSetTrait
{
    private array $routes = [];

    /**
     * Count number of routes.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->routes, COUNT_RECURSIVE);
    }

    /**
     * Get a route by name.
     *
     * @param string $name
     *
     * @return Route|null
     */
    public function getRoute(string $name): ?Route
    {
        /** @var Route $route */
        foreach ($this->routes as $route) {
            if (true === $route->isGroup()) {
                if (null !== ($found = $route->getRoute($name))) {
                    return $found;
                }
                continue;
            }

            if ($route->getName() === $name) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Get routes.
     *
     * @return Generator<RouteInterface>
     */
    public function getRoutes(): Generator
    {
        /** @var Route $route */
        foreach ($this->routes as $route) {
            if (true === $route->isGroup()) {
                yield from $route->getRoutes();
                continue;
            }

            yield $route;
        }
    }

    /**
     * Search route.
     *
     * @param ServerRequestInterface $request
     * @param array $attributes
     *
     * @return RouteInterface|null
     */
    public function searchRoute(ServerRequestInterface $request, array &$attributes = []): ?RouteInterface
    {
        /** @var RouteInterface $route */
        foreach ($this->getRoutes() as $route) {
            if ($route->test($request, $attributes)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Add route.
     *
     * @param RouteInterface ...$route
     *
     * @return static
     */
    public function addRoute(RouteInterface ...$route): static
    {
        array_push($this->routes, ...$route);

        if ($this instanceof RouteInterface) {
            array_walk($route, fn($route) => $route->setParent($this));
        }

        $this->sortRoutes();

        return $this;
    }

    /**
     * Sort routes.
     */
    private function sortRoutes(): void
    {
        usort(
            $this->routes,
            function (RouteInterface $route1, RouteInterface $route2) {
                return (int)$route2->getPriority() <=> (int)$route1->getPriority();
            }
        );
    }
}