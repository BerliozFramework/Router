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

use Berlioz\Router\Exception\RoutingException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RouterInterface.
 *
 * @package Berlioz\Router
 */
interface RouterInterface extends RouteSetInterface
{
    /**
     * Generate route.
     *
     * @param string|RouteInterface $route
     * @param array|RouteAttributes $parameters
     *
     * @return string
     * @throws RoutingException
     */
    public function generate(string|RouteInterface $route, array|RouteAttributes $parameters = []): string;

    /**
     * Finalize path.
     *
     * Prepends the reverse-proxy prefix (`X-Forwarded-Prefix`) to the given path
     * when the request comes from a trusted proxy.
     *
     * @param string $path
     * @param array|null $serverParams Explicit parameters; null uses the current context, then `$_SERVER`
     *
     * @return string
     */
    public function finalizePath(string $path, ?array $serverParams = null): string;

    /**
     * Set the request context used to finalize paths and generate URLs.
     *
     * The context must not be serialized. An empty array disables fallback to globals;
     * null clears the context and restores the `$_SERVER` fallback.
     *
     * @param array|null $serverParams Current request server parameters
     *
     * @return void
     */
    public function setServerParams(?array $serverParams): void;

    /**
     * Is valid request?
     *
     * @param ServerRequestInterface|string $request
     *
     * @return bool
     */
    public function isValid(ServerRequestInterface|string $request): bool;

    /**
     * Handle server request.
     *
     * @param ServerRequestInterface $request
     *
     * @return RouteInterface|null
     */
    public function handle(ServerRequestInterface &$request): ?RouteInterface;
}
