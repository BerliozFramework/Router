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

use Countable;
use Psr\Http\Message\UriInterface;
use Serializable;

/**
 * Interface RouteSetInterface
 *
 * @package Berlioz\Router
 */
interface RouteSetInterface extends Countable, Serializable
{
    /**
     * Add new route.
     *
     * @param \Berlioz\Router\RouteInterface $route Route to add
     *
     * @return \Berlioz\Router\RouteSetInterface
     * @throws \Berlioz\Router\Exception\RoutingException If route already exists
     */
    public function addRoute(RouteInterface $route): RouteSetInterface;

    /**
     * Get routes.
     *
     * @return \Berlioz\Router\RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Merge route set with another.
     *
     * @param \Berlioz\Router\RouteSetInterface $routeSet
     *
     * @return \Berlioz\Router\RouteSetInterface
     */
    public function merge(RouteSetInterface $routeSet): RouteSetInterface;

    /**
     * Get routes by name.
     *
     * @param string $name Name of route
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getByName($name): ?RouteInterface;

    /**
     * Search route for given uri and method.
     *
     * @param \Psr\Http\Message\UriInterface $uri Uri
     * @param string|null $method Http method
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function searchRoute(UriInterface $uri, string $method = null): ?RouteInterface;
}