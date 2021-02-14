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

namespace Berlioz\Router\Exception;

use Exception;

/**
 * Class RoutingException.
 *
 * @package Berlioz\Router\Exception
 */
class RoutingException extends Exception
{
    /**
     * Missing attribute.
     *
     * @param string $name
     * @param string|null $route
     *
     * @return static
     */
    public static function missingAttribute(string $name, ?string $route): static
    {
        if (null === $route) {
            return new static(sprintf('Missing attribute "%s" to generate route', $name));
        }

        return new static(sprintf('Missing attribute "%s" to generate route "%s"', $name, $route));
    }
}