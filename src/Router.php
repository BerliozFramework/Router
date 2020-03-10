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

use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream\PhpInputStream;
use Berlioz\Http\Message\UploadedFile;
use Berlioz\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Router.
 *
 * @package Berlioz\Router
 * @see \Berlioz\Router\RouterInterface
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
    public function serialize(): string
    {
        return serialize(['routeSet' => $this->routeSet]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $tmpUnserialized = unserialize($serialized);

        $this->routeSet = $tmpUnserialized['routeSet'];
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
        if (null === $this->routeSet) {
            $this->routeSet = new RouteSet();
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
     * Get HTTP headers.
     *
     * @return array
     */
    private static function getHeaders(): array
    {
        // Get all headers
        if (function_exists('\getallheaders')) {
            return \getallheaders() ?: [];
        }

        $headers = [];

        $serverVars = $_SERVER;
        $serverVars['HTTP_CONTENT_TYPE'] = $serverVars['HTTP_CONTENT_TYPE'] ?? $serverVars['CONTENT_TYPE'] ?? null;
        $serverVars['HTTP_CONTENT_LENGTH'] = $serverVars['HTTP_CONTENT_LENGTH'] ?? $serverVars['CONTENT_LENGTH'] ?? null;

        foreach ($serverVars as $name => $value) {
            if (substr($name, 0, 5) !== 'HTTP_') {
                continue;
            }

            if (null === $value) {
                continue;
            }

            $headers[str_replace(
                ' ',
                '-',
                ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
            )] = $value;
        }

        return $headers;
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
        } elseif (isset($_SERVER['REQUEST_METHOD'])) {
            $method = mb_strtoupper($_SERVER['REQUEST_METHOD']);
        } else {
            $method = 'GET';
        }

        // Path
        $path = null;
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        // Query string
        $queryString = $_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING'] ?? '';

        // Request URI
        $requestUri = new Uri(
            $_SERVER['REQUEST_SCHEME'] ?? '',
            $_SERVER['HTTP_HOST'] ?? '',
            $_SERVER['SERVER_PORT'] ?? 80,
            $path,
            $queryString,
            '',
            $_SERVER['PHP_AUTH_USER'] ?? '',
            $_SERVER['PHP_AUTH_PW'] ?? ''
        );

        // Server request
        return new ServerRequest(
            $method,
            $requestUri,
            static::getHeaders(),
            $_COOKIE,
            $_SERVER,
            new PhpInputStream(),
            UploadedFile::parseUploadedFiles($_FILES)
        );
    }

    /**
     * @inheritdoc
     */
    public function getServerRequest(): ServerRequestInterface
    {
        if (null === $this->serverRequest) {
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
        if (null === ($route = $this->getRouteSet()->getByName($name))) {
            return false;
        }

        return $route->generate($parameters);
    }

    /**
     * @inheritdoc
     */
    public function isValid(string $path, string $method = Request::HTTP_METHOD_GET): bool
    {
        $uri = Uri::createFromString($path);

        return null !== ($this->getRouteSet()->searchRoute($uri, $method));
    }

    /**
     * @inheritdoc
     */
    public function handle(?ServerRequestInterface &$serverRequest = null): ?RouteInterface
    {
        // Log
        $this->log('debug', sprintf('%s', __METHOD__));

        // Server request
        if (null === $serverRequest) {
            $serverRequest = $this->getServerRequest();

            // Log
            $this->log('debug', sprintf('%s / ServerRequest created', __METHOD__));
        }

        $route = $this->getRouteSet()->searchRoute($serverRequest->getUri(), $serverRequest->getMethod());
        if (null === $route) {
            return null;
        }

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
}