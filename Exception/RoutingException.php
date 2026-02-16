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
     * Missing attributes.
     *
     * @param array $attributes
     * @param string|null $route
     *
     * @return static
     */
    public static function missingAttributes(array $attributes, ?string $route): static
    {
        array_walk($attributes, fn(string &$attribute) => $attribute = '"' . $attribute . '"');

        if (null === $route) {
            return new static(sprintf('Missing attributes %s to generate route', implode(', ', $attributes)));
        }

        return new static(
            sprintf(
                'Missing attributes %s to generate route "%s"',
                implode(', ', $attributes),
                $route
            )
        );
    }
}