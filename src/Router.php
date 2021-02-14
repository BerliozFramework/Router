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

use Berlioz\Router\Exception\AmbiguousException;
use Berlioz\Router\Exception\NotFoundException;
use Berlioz\Router\Exception\RoutingException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class Router.
 *
 * @package Berlioz\Router
 */
class Router implements RouterInterface
{
    use LoggerAwareTrait;
    use RouteSetTrait;

    /**
     * Router constructor.
     *
     * @param LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        if (null !== $logger) {
            $this->setLogger($logger);
        }
    }

    /**
     * PHP serialize method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return ['routes' => $this->routes];
    }

    /**
     * PHP unserialize method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->routes = $data['routes'];
    }

    /**
     * Log.
     *
     * @param string $level
     * @param string $message
     */
    protected function log(string $level, string $message): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->log($level, $message);
    }

    /**
     * Generate route.
     *
     * @param string $name
     * @param array|RouteAttributes $parameters
     *
     * @return string
     * @throws RoutingException
     */
    public function generate(string $name, array|RouteAttributes $parameters = []): string
    {
        $routesFound = [];
        $exceptions = [];
        $parameters = $this->generateParameters($parameters);

        /** @var Route $route */
        foreach ($this->getRoutes($name) as $route) {
            try {
                $nbUsed = 0;
                $path = $route->generate($parameters, $nbUsed);
                $routesFound[$nbUsed][] = $path;
            } catch (RoutingException $e) {
                $exceptions[] = $e;
            }
        }

        krsort($routesFound);

        // Get first routes or return false if not found
        if (false === ($routesFound = reset($routesFound))) {
            if (!empty($exceptions)) {
                throw reset($exceptions);
            }

            throw new NotFoundException(sprintf('Route "%s" not found with given parameters', $name));
        }

        if (count($routesFound) > 1) {
            throw AmbiguousException::multiple($name);
        }

        return reset($routesFound);
    }

    private function generateParameters(array|RouteAttributes $parameters = []): array
    {
        if ($parameters instanceof RouteAttributes) {
            return $parameters->routeAttributes();
        }

        $finalParameters = [];

        array_walk(
            $parameters,
            function ($parameter, $key) use (&$finalParameters) {
                if ($parameter instanceof RouteAttributes) {
                    $finalParameters = array_merge($finalParameters, $parameter->routeAttributes());
                    return;
                }

                if (is_array($parameter)) {
                    $finalParameters = array_merge($finalParameters, $parameter);
                    return;
                }

                $finalParameters = array_merge($finalParameters, [$key => $parameter]);
            }
        );

        return $finalParameters;
    }

    /**
     * Is valid request?
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isValid(ServerRequestInterface $request): bool
    {
        /** @var Route $route */
        foreach ($this->getRoutes() as $route) {
            if ($route->test($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle server request.
     *
     * @param ServerRequestInterface $request
     *
     * @return RouteInterface|null
     */
    public function handle(ServerRequestInterface &$request): ?RouteInterface
    {
        // Log
        $this->log('debug', sprintf('%s', __METHOD__));

        $attributes = [];
        $route = $this->searchRoute($request, $attributes);

        if (null !== $route) {
            // Log
            $this->log('debug', sprintf('%s / Route found', __METHOD__));

            // Add attributes to server request
            foreach ($attributes as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }

        // Log
        $this->log('debug', sprintf('%s / ServerRequest completed', __METHOD__));

        return $route;
    }
}