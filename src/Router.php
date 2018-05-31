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

namespace Berlioz\Router;

use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\UploadedFile;
use Berlioz\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Router.
 *
 * @package Berlioz\Router
 * @see     \Berlioz\Router\RouterInterface
 */
class Router implements RouterInterface
{
    use LoggerAwareTrait;
    /** @var \Berlioz\Router\RouteSetInterface Route set */
    private $routeSet;
    /** @var \Psr\Http\Message\ServerRequestInterface Server request */
    private $serverRequest;

    /**
     * @inheritdoc
     */
    public function __sleep(): array
    {
        return ['routeSet'];
    }

    /**
     * Log.
     *
     * @param string $level
     * @param string $message
     */
    protected function log($level, $message)
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }

    /**
     * @inheritdoc
     */
    public function getRouteSet(): RouteSetInterface
    {
        if (is_null($this->routeSet)) {
            $this->routeSet = new RouteSet;
        }

        return $this->routeSet;
    }

    /**
     * @inheritdoc
     */
    public function setRouteSet(RouteSetInterface $routeSet): RouterInterface
    {
        $this->routeSet = $routeSet;

        return $this;
    }

    /**
     * Make server request.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function makeServerRequest(): ServerRequestInterface
    {
        // HTTP Method
        if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = mb_strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } else {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $method = mb_strtoupper($_SERVER['REQUEST_METHOD']);
            } else {
                $method = 'GET';
            }
        }

        // Path
        $path = null;
        if (isset($_SERVER['REDIRECT_URL'])) {
            $path = $_SERVER['REDIRECT_URL'];
        } else {
            if (isset($_SERVER['REQUEST_URI'])) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            }
        }

        // Query string
        $queryString = $_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING'] ?? '';

        // Headers
        $headers = [];
        // Get all headers
        if (function_exists('\getallheaders')) {
            $headers = \getallheaders() ?: [];
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        // Get stream
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);

        // Request URI
        $requestUri = new Uri($_SERVER['REQUEST_SCHEME'] ?? '',
                              $_SERVER['HTTP_HOST'] ?? '',
                              $_SERVER['SERVER_PORT'] ?? 80,
                              $path,
                              $queryString,
                              '',
                              $_SERVER['PHP_AUTH_USER'] ?? '',
                              $_SERVER['PHP_AUTH_PW'] ?? '');

        // Server request
        $serverRequest = new ServerRequest($method,
                                           $requestUri,
                                           $headers,
                                           $_COOKIE,
                                           $_SERVER,
                                           new Stream($stream),
                                           UploadedFile::parseUploadedFiles($_FILES));

        return $serverRequest;
    }

    /**
     * @inheritdoc
     */
    public function getServerRequest(): ServerRequestInterface
    {
        if (is_null($this->serverRequest)) {
            $this->serverRequest = static::makeServerRequest();
        }

        return $this->serverRequest;
    }

    /**
     * @inheritdoc
     */
    public function setServerRequest(ServerRequestInterface $serverRequest): RouterInterface
    {
        $this->serverRequest = $serverRequest;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function generate(string $name, array $parameters = [])
    {
        if (!is_null($route = $this->getRouteSet()->getByName($name))) {
            $routeGenerated = $route->generate($parameters);

            if ($routeGenerated !== false) {
                return $routeGenerated;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isValid(string $path, string $method = Request::HTTP_METHOD_GET): bool
    {
        $uri = Uri::createFromString($path);

        return !is_null($this->getRouteSet()->searchRoute($uri, $method));
    }

    /**
     * @inheritdoc
     */
    public function handle(?ServerRequestInterface &$serverRequest = null): ?RouteInterface
    {
        // Log
        $this->log('debug', sprintf('%s', __METHOD__));

        // Server request
        if (is_null($serverRequest)) {
            $serverRequest = $this->getServerRequest();

            // Log
            $this->log('debug', sprintf('%s / ServerRequest created', __METHOD__));
        }

        /** @var \Berlioz\Router\RouteInterface $route */
        if (!is_null($route = $this->getRouteSet()->searchRoute($serverRequest->getUri(), $serverRequest->getMethod()))) {
            // Log
            $this->log('debug', sprintf('%s / Route found', __METHOD__));

            // Attributes
            foreach ($route->extractAttributes($serverRequest->getUri()->getPath()) as $name => $value) {
                $serverRequest = $serverRequest->withAttribute($name, $value);
            }
            $this->setServerRequest($serverRequest);

            // Log
            $this->log('debug', sprintf('%s / ServerRequest completed', __METHOD__));

            return $route;
        }

        return null;
    }
}