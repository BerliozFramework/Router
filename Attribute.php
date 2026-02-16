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

/**
 * Class Attribute.
 *
 * @package Berlioz\Router
 */
class Attribute
{
    public const TYPES = [
        'int' => '\d+',
        'float' => '\d+(\.\d+)',
        'uuid' => '[0-9A-Fa-f]{8}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{12}',
        'uuid4' => '[0-9A-Fa-f]{8}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{12}',
        'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'md5' => '[0-9a-fA-F]{32}',
        'sha1' => '[0-9a-fA-F]{40}',
        'domain' => '([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}',
    ];
    public const DEPRECATED_TYPES = [
        'uuid' => 'uuid4',
    ];

    /**
     * Attribute constructor.
     *
     * @param string $name
     * @param string|int|float|bool|null $default
     * @param string|null $regex
     * @param Route|null $route
     */
    public function __construct(
        private string $name,
        private string|int|float|bool|null $default = null,
        private ?string $regex = null,
        private Route|null $route = null,
    ) {
    }

    /**
     * Serialize PHP magic method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'default' => $this->default,
            'regex' => $this->regex
        ];
    }

    /**
     * Unserialize PHP magic method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->default = $data['default'];
        $this->regex = $data['regex'];
        $this->route = null;
    }

    /**
     * Set route.
     *
     * @param Route|null $route
     */
    public function setRoute(?Route $route): void
    {
        $this->route = $route;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get default value.
     *
     * @return string|int|float|bool|null
     */
    public function getDefault(): string|int|float|bool|null
    {
        return $this->default ?? $this->route?->getParent()?->getAttribute($this->name)?->getDefault();
    }

    /**
     * Set default.
     *
     * @param string|int|float|bool|null $default
     */
    public function setDefault(string|int|float|bool|null $default): void
    {
        $this->default = $default;
    }

    /**
     * Has default?
     *
     * @return bool
     */
    public function hasDefault(): bool
    {
        return null !== $this->getDefault();
    }

    /**
     * Get regex validation.
     *
     * @return string|null
     */
    public function getRegex(): ?string
    {
        return $this->regex ?? $this->route?->getParent()?->getAttribute($this->name)?->getRegex();
    }

    /**
     * Set regex.
     *
     * @param string|null $regex
     */
    public function setRegex(?string $regex): void
    {
        $this->regex = $regex;
    }

    /**
     * Has regex?
     *
     * @return bool
     */
    public function hasRegex(): bool
    {
        return null !== $this->getRegex();
    }
}