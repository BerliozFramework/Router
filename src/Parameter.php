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

use Serializable;

/**
 * Class Parameter.
 *
 * @package Berlioz\Router
 */
class Parameter implements Serializable
{
    /** @var string Name of parameter */
    private $name;
    /** @var mixed Default value */
    private $defaultValue;
    /** @var string Regex validation */
    private $regexValidation;

    /**
     * Parameter constructor.
     *
     * @param string $name
     * @param string|null $defaultValue
     * @param string|null $regexValidation
     */
    public function __construct(string $name, string $defaultValue = null, string $regexValidation = null)
    {
        $this->name = $name;
        $this->defaultValue = $defaultValue;
        $this->regexValidation = $regexValidation;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize(
            [
                'name' => $this->name,
                'defaultValue' => $this->defaultValue,
                'regexValidation' => $this->regexValidation
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $tmpUnserialized = unserialize($serialized);

        $this->name = $tmpUnserialized['name'];
        $this->defaultValue = $tmpUnserialized['defaultValue'];
        $this->regexValidation = $tmpUnserialized['regexValidation'];
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
     * Has default value ?
     *
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return null !== $this->defaultValue;
    }

    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Get regex validation.
     *
     * @return string
     */
    public function getRegexValidation(): string
    {
        return $this->regexValidation ?? '[^/]+';
    }
}