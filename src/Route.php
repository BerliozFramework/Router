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
use Berlioz\Router\Exception\RoutingException;

class Route implements RouteInterface
{
    const REGEX_PARAMETER = '/{(?<name>[\w_]+)}/';
    /** @var string Route */
    private $route;
    /** @var string[][] Route options */
    private $options;
    /** @var string Route regex */
    private $route_regex;
    /** @var array Context */
    private $context;
    /** @var \Berlioz\Router\Parameter[] Parameters */
    private $parameters;
    /** @var mixed[] Parameters values */
    private $parameters_values;

    /**
     * Route constructor.
     *
     * @param string $route
     * @param array  $options
     * @param array  $context
     */
    public function __construct(string $route, array $options = [], array $context = [])
    {
        $this->route = $route;
        $this->options = $options;
        $this->context = $context;
        $this->parameters = [];
        $this->parameters_values = [];

        // Read parameters
        $matchesParams = [];
        if (preg_match_all(self::REGEX_PARAMETER, $this->route, $matchesParams) > 0) {
            foreach ($matchesParams['name'] as $match) {
                if (!isset($this->parameters[$match])) {
                    $parameter = new Parameter($match,
                                               $this->options['defaults'][$match] ?? null,
                                               $this->options['requirements'][$match] ?? null);

                    $this->parameters[$match] = $parameter;
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        if (!empty($this->options['name']) && is_string($this->options['name'])) {
            return $this->options['name'];
        } else {
            return spl_object_hash($this);
        }
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getContext(): array
    {
        return $this->context ?? [];
    }

    /**
     * @inheritdoc
     */
    public function setContext(array $context): RouteInterface
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMethods(): array
    {
        $defaultMethods = [Request::HTTP_METHOD_GET,
                           Request::HTTP_METHOD_HEAD,
                           Request::HTTP_METHOD_POST,
                           Request::HTTP_METHOD_OPTIONS,
                           Request::HTTP_METHOD_CONNECT,
                           Request::HTTP_METHOD_TRACE,
                           Request::HTTP_METHOD_PUT,
                           Request::HTTP_METHOD_DELETE];

        $methods = [];
        if (isset($this->options['method'])) {
            if (is_scalar($this->options['method'])) {
                $methods = explode(',', (string) $this->options['method']);
            } else {
                $methods = (array) $this->options['method'];
            }
            $methods = array_map('mb_strtoupper', $methods);
            $methods = array_map('trim', $methods);
        }
        $methods = array_intersect($methods, $defaultMethods);

        if (empty($methods)) {
            return $defaultMethods;
        } else {
            return $methods;
        }
    }

    /**
     * @inheritdoc
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @inheritdoc
     */
    public function test(string $test): bool
    {
        return preg_match($this->getRouteRegex(), $test) == 1;
    }

    /**
     * @inheritdoc
     */
    public function generate(array $parameters)
    {
        $route = $this->getRoute();

        $parametersFound = [];
        foreach ($this->parameters as $parameter) {
            if (!empty($parameters[$parameter->getName()])) {
                $value = (string) $parameters[$parameter->getName()];
            } else {
                if ($parameter->hasDefaultValue()) {
                    $value = (string) $parameter->getDefaultValue();
                } else {
                    return false;
                }
            }

            $route = str_replace('{' . $parameter->getName() . '}', $value, $route);
            $parametersFound[] = $parameter->getName();
        }

        // Not found parameters
        $getParameters = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            if (!in_array($parameterName, $parametersFound)) {
                $getParameters[$parameterName] = $parameterValue;
            }
        }

        // Construct query string
        if (!empty($getParameters)) {
            $getParameters = $this->filterParameters($getParameters);
            array_walk_recursive(
                $getParameters,
                function (&$value) {
                    $value = (string) $value;
                });
            $httpBuildQuery = http_build_query($getParameters);

            if (!empty($httpBuildQuery)) {
                $route .= '?' . $httpBuildQuery;
            }
        }

        return $route;
    }

    /**
     * @inheritdoc
     */
    public function extractAttributes(string $path): array
    {
        $matches = [];

        if (preg_match($this->getRouteRegex(), $path, $matches) == 1) {
            return array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
        } else {
            throw new RoutingException(sprintf('Given path "%s" isn\'t valid for Route "%s"', $path, $this->getName()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfParameters(): int
    {
        return count($this->parameters);
    }

    /**
     * Get route with regex replacements.
     *
     * @return string
     */
    private function getRouteRegex(): string
    {
        if (is_null($this->route_regex)) {
            $route = $this;
            $this->route_regex =
                '~^' .
                preg_replace_callback(
                    self::REGEX_PARAMETER,
                    function ($match) use ($route) {
                        if (isset($route->parameters[$match['name']])) {
                            return '(?<' . $match['name'] . '>' . $parameter = $route->parameters[$match['name']]->getRegexValidation() . ')';
                        } else {
                            return $match[0];
                        }
                    },
                    $this->route) .
                '$~i';
        }

        return $this->route_regex;
    }

    /**
     * Filter parameters, and remove null parameters.
     *
     * @param array $params Parameters
     *
     * @return array
     */
    private function filterParameters(array $params): array
    {
        return
            array_filter(
                $params,
                function (&$value) {
                    if (is_array($value)) {
                        $value = $this->filterParameters($value);

                        return count($value) > 0;
                    } else {
                        return !is_null($value);
                    }
                }
            );
    }
}