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
 * Interface RouteInterface.
 *
 * @package Berlioz\Router
 */
interface RouteInterface extends RouteSetInterface
{
    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Get attribute.
     *
     * @param string $name
     *
     * @return Attribute|null
     */
    public function getAttribute(string $name): ?Attribute;

    /**
     * Get methods.
     *
     * @return array
     */
    public function getMethods(): array;

    /**
     * Get hosts.
     *
     * @return array|null
     */
    public function getHosts(): ?array;

    /**
     * Get priority.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Get option.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption(string $name, mixed $default = null): mixed;

    /**
     * Get context.
     *
     * @return mixed
     */
    public function getContext(): mixed;

    /**
     * Set context.
     *
     * @param mixed $context
     *
     * @return static
     */
    public function setContext(mixed $context): static;

    /**
     * Is group?
     *
     * @return bool
     */
    public function isGroup(): bool;

    /**
     * Set parent.
     *
     * @param Route $parent
     *
     * @return static
     */
    public function setParent(Route $parent): static;

    /**
     * Test server request.
     *
     * @param ServerRequestInterface $request
     * @param array $attributes
     *
     * @return bool
     */
    public function test(ServerRequestInterface $request, array &$attributes = []): bool;

    /**
     * Generate route.
     *
     * @param array $parameters Parameters
     * @param int $nbUsed
     *
     * @return string
     * @throws RoutingException
     */
    public function generate(array $parameters = [], int &$nbUsed = 0): string;

    /**
     * Compile route.
     */
    public function compile(): void;
}