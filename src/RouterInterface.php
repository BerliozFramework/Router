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


use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /**
     * Get route set.
     *
     * @return \Berlioz\Router\RouteSetInterface
     */
    public function getRouteSet(): RouteSetInterface;

    /**
     * Set route set.
     *
     * @param \Berlioz\Router\RouteSetInterface $routeSet
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
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getServerRequest(): ServerRequestInterface;

    /**
     * Set server request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
     *
     * @return \Berlioz\Router\RouterInterface
     */
    public function setServerRequest(ServerRequestInterface $serverRequest): RouterInterface;

    /**
     * Is valid route ?
     *
     * Check if a route is associate to the given path and HTTP method.
     *
     * @param string $path   Path to test
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
     * @param string $name       Name of route
     * @param array  $parameters Parameters for route
     *
     * @return string|false
     */
    public function generate(string $name, array $parameters = []);

    /**
     * Handle.
     *
     * @param \Psr\Http\Message\ServerRequestInterface|null $serverRequest Server request
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function handle(?ServerRequestInterface $serverRequest = null): ?RouteInterface;
}