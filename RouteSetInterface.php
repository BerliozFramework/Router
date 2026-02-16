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

use Countable;
use Generator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RouteSetInterface.
 *
 * @package Berlioz\Router
 */
interface RouteSetInterface extends Countable
{
    /**
     * Count number of routes.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get a route by name.
     *
     * @param string $name
     *
     * @return Route|null
     */
    public function getRoute(string $name): ?Route;

    /**
     * Get routes.
     *
     * @return Generator<RouteInterface>
     */
    public function getRoutes(): Generator;

    /**
     * Search route.
     *
     * @param ServerRequestInterface $request
     * @param array $attributes
     *
     * @return RouteInterface|null
     */
    public function searchRoute(ServerRequestInterface $request, array &$attributes = []): ?RouteInterface;

    /**
     * Add route.
     *
     * @param RouteInterface ...$route
     *
     * @return static
     */
    public function addRoute(RouteInterface ...$route): static;
}