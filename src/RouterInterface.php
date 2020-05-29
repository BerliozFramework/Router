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

use Berlioz\Router\Exception\RoutingException;
use Psr\Http\Message\ServerRequestInterface;
use Serializable;

/**
 * Interface RouterInterface.
 *
 * @package Berlioz\Router
 */
interface RouterInterface extends Serializable
{
    /**
     * Get route set.
     *
     * @return RouteSetInterface
     */
    public function getRouteSet(): RouteSetInterface;

    /**
     * Set route set.
     *
     * @param RouteSetInterface $routeSet
     *
     * @return static
     */
    public function setRouteSet(RouteSetInterface $routeSet): RouterInterface;

    /**
     * Get server request.
     *
     * Can called after RouterInterface::handle() method.
     * Return the ServerRequest object of current request.
     *
     * @return ServerRequestInterface
     */
    public function getServerRequest(): ServerRequestInterface;

    /**
     * Set server request.
     *
     * @param ServerRequestInterface $serverRequest
     *
     * @return RouterInterface
     */
    public function setServerRequest(ServerRequestInterface $serverRequest): RouterInterface;

    /**
     * Is valid route ?
     *
     * Check if a route is associate to the given path and HTTP method.
     *
     * @param string $path Path to test
     * @param string $method Http method
     *
     * @return bool
     */
    public function isValid(string $path, string $method = 'GET'): bool;

    /**
     * Generate route with parameters.
     *
     * Must return path route with given name of route and associated parameters.
     *
     * @param string $name Name of route
     * @param array $parameters Parameters for route
     *
     * @return string|false
     */
    public function generate(string $name, array $parameters = []);

    /**
     * Handle.
     *
     * @param ServerRequestInterface|null $serverRequest Server request
     *
     * @return RouteInterface|null
     * @throws RoutingException
     */
    public function handle(?ServerRequestInterface &$serverRequest = null): ?RouteInterface;
}