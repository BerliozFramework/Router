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

namespace Berlioz\Router\Tests;

use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractTestCase extends TestCase
{
    protected function getServerRequest(
        string $path = 'https://www.phpunit.com/path/test/sub-path',
        string $method = Request::HTTP_METHOD_GET
    ): ServerRequestInterface {
        return new ServerRequest($method, Uri::createFromString($path), [], [], [], new Stream());
    }

}