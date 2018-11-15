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

interface RouteInterface
{
    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Get context.
     *
     * @return array
     */
    public function getContext(): array;

    /**
     * Get context.
     *
     * @param array $context
     *
     * @return static
     */
    public function setContext(array $context): RouteInterface;

    /**
     * Get methods.
     *
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * Get route.
     *
     * @return string|null
     */
    public function getRoute(): string;

    /**
     * Test route with path.
     *
     * @param string $test Path to test
     *
     * @return bool
     */
    public function test(string $test): bool;

    /**
     * Extract attributes from path.
     *
     * @param string $path Path
     *
     * @return array
     * @throws \Berlioz\Router\Exception\RoutingException If given path do not contain all attributes or no defaults
     *                                                    values available.
     */
    public function extractAttributes(string $path): array;

    /**
     * Generate route with parameters.
     *
     * @param array $parameters Parameters
     *
     * @return string|false
     */
    public function generate(array $parameters);

    /**
     * Get number of parameters.
     *
     * @return int
     */
    public function getNumberOfParameters(): int;
}