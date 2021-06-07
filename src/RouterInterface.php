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
     * @param string $name
     * @param array|RouteAttributes $parameters
     *
     * @return string
     * @throws RoutingException
     */
    public function generate(string $name, array|RouteAttributes $parameters = []): string;

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