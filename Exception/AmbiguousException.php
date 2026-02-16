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

/**
 * Class AmbiguousException.
 *
 * @package Berlioz\Router\Exception
 */
class AmbiguousException extends RoutingException
{
    /**
     * Multiple possible routes.
     *
     * @param string $name
     *
     * @return static
     */
    public static function multiple(string $name)
    {
        return new static(sprintf('Multiple possible routes named "%s" with given parameters', $name));
    }
}