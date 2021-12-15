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

use Berlioz\Http\Message\Request;
use Berlioz\Router\Exception\RoutingException;
use Psr\Http\Message\ServerRequestInterface;

use const ARRAY_FILTER_USE_KEY;

/**
 * Class Route.
 *
 * @package Berlioz\Router
 */
class Route implements RouteInterface
{
    use RouteSetTrait;

    protected const REGEX_ATTRIBUTE = '/{(?<name>' . Route::REGEX_ATTRIBUTE_NAME . ')(?:::(?<type>\w+)|:(?<regex>[^}]+))?}/i';
    protected const REGEX_ATTRIBUTE_NAME = '[\w_]+';
    protected const REGEX_ATTRIBUTE_VALUE = '[^/]+';
    private ?array $method = null;
    private ?array $host = null;
    /** @var Attribute[] Attributes */
    protected array $attributes = [];
    protected ?Route $parent = null;
    protected ?string $path_regex = null;

    /**
     * Route constructor.
     *
     * @param string $path
     * @param array $defaults
     * @param array $requirements
     * @param string|null $name
     * @param string|array|null $method
     * @param string|array|null $host
     * @param int $priority
     * @param array $options
     * @param mixed $context
     *
     * @throws RoutingException
     */
    public function __construct(
        protected string $path = '',
        array $defaults = [],
        array $requirements = [],
        protected ?string $name = null,
        string|array|null $method = null,
        string|array|null $host = null,
        protected int $priority = -1,
        protected array $options = [],
        protected mixed $context = null,
    ) {
        // Extract attributes from path
        $this->path =
            (string)preg_replace_callback(
                static::REGEX_ATTRIBUTE,
                function ($matches) {
                    $name = $matches['name'];

                    // Duplicated attribute
                    if (!array_key_exists($name, $this->attributes)) {
                        $attribute = new Attribute($name, route: $this);
                        $this->attributes[$name] = $attribute;

                        if (!empty($matches['regex'])) {
                            $attribute->setRegex($matches['regex']);
                        }
                        if (!empty($matches['type'])) {
                            $this->attributes[$name]->setRegex(
                                Attribute::TYPES[$matches['type']] ??
                                throw new RoutingException(sprintf('Unknown type "%s"', $matches['type']))
                            );
                        }
                    }

                    return '{' . $name . '}';
                },
                $this->path
            );

        // Define requirement attributes
        foreach ($requirements as $name => $requirement) {
            $attribute = $this->attributes[$name] ?? $this->attributes[$name] = new Attribute($name, route: $this);
            $attribute->setRegex($requirement);
        }

        // Define default attributes
        foreach ($defaults as $name => $default) {
            $attribute = $this->attributes[$name] ?? $this->attributes[$name] = new Attribute($name, route: $this);
            $attribute->setDefault($default);
        }

        if (null !== $method) {
            $this->method = (array)$method;
            array_walk($this->method, fn(&$value) => $value = strtoupper($value));
        }

        if (null !== $host) {
            $this->host = (array)$host;
            array_walk($this->host, fn(&$value) => $value = strtolower($value));
        }
    }

    /**
     * PHP serialize method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        $this->compile();

        return [
            'path' => $this->path,
            'name' => $this->name,
            'method' => $this->method,
            'host' => $this->host,
            'priority' => $this->priority,
            'attributes' => $this->attributes,
            'options' => $this->options,
            'context' => $this->context,
            'path_regex' => $this->path_regex,
            'routes' => $this->routes,
        ];
    }

    /**
     * PHP unserialize method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->path = $data['path'];
        $this->name = $data['name'];
        $this->method = $data['method'];
        $this->host = $data['host'];
        $this->priority = $data['priority'];
        $this->attributes = $data['attributes'];
        $this->options = $data['options'];
        $this->context = $data['context'];
        $this->path_regex = $data['path_regex'];
        $this->routes = $data['routes'];

        // Set group parent
        array_map(fn(Route $route) => $route->setParent($this), $this->routes);
        array_map(fn(Attribute $attribute) => $attribute->setRoute($this), $this->attributes);
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = $this->parent?->getPath();

        return (($path ?? '') . $this->path) ?? '';
    }

    /**
     * Get path regex.
     *
     * @return string
     */
    protected function getPathRegex(): string
    {
        if (null === $this->path_regex) {
            $this->compile();
        }

        return $this->path_regex;
    }

    /**
     * Get attribute.
     *
     * @param string $name
     *
     * @return Attribute|null
     */
    public function getAttribute(string $name): ?Attribute
    {
        return $this->attributes[$name] ?? $this->parent?->getAttribute($name);
    }

    /**
     * Get methods.
     *
     * @return array
     */
    public function getMethods(): array
    {
        $defaultMethods = [
            Request::HTTP_METHOD_GET,
            Request::HTTP_METHOD_HEAD,
            Request::HTTP_METHOD_POST,
            Request::HTTP_METHOD_OPTIONS,
            Request::HTTP_METHOD_CONNECT,
            Request::HTTP_METHOD_TRACE,
            Request::HTTP_METHOD_PUT,
            Request::HTTP_METHOD_DELETE,
        ];

        if (null === $this->method) {
            if ($this->parent) {
                return $this->parent->getMethods();
            }

            return $defaultMethods;
        }

        return array_intersect($this->method, $defaultMethods);
    }

    /**
     * Get hosts.
     *
     * @return array|null
     */
    public function getHosts(): ?array
    {
        return $this->host;
    }

    /**
     * Get priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get options.
     */
    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    /**
     * Get option.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $this->parent?->getOption($name, $default) ?? $default;
    }

    /**
     * Get context.
     *
     * @return mixed
     */
    public function getContext(): mixed
    {
        return $this->context;
    }

    /**
     * Set context.
     *
     * @param mixed $context
     *
     * @return static
     */
    public function setContext(mixed $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Is group?
     *
     * @return bool
     */
    public function isGroup(): bool
    {
        return count($this->routes) > 0;
    }

    /**
     * Get parent.
     *
     * @return Route|null
     */
    public function getParent(): ?Route
    {
        return $this->parent;
    }

    /**
     * Set parent.
     *
     * @param Route $parent
     *
     * @return static
     */
    public function setParent(Route $parent): static
    {
        if ($this->parent !== $parent) {
            $this->path_regex = null;
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * Test server request.
     *
     * @param ServerRequestInterface $request
     * @param array $attributes
     *
     * @return bool
     */
    public function test(ServerRequestInterface $request, array &$attributes = []): bool
    {
        // Cannot be tested because is a group
        if ($this->isGroup()) {
            return false;
        }

        $matches = [];
        if (preg_match('~^' . $this->getPathRegex() . '$~i', $request->getUri()->getPath(), $matches) !== 1) {
            return false;
        }

        // Accepted method?
        if (!in_array(strtoupper($request->getMethod()), $this->getMethods())) {
            return false;
        }

        // Accepted host?
        if (null !== $this->getHosts()) {
            if (!in_array(strtolower($request->getUri()->getHost()), $this->getHosts())) {
                return false;
            }
        }

        $attributes = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        return true;
    }

    /**
     * Generate route.
     *
     * @param array $parameters Parameters
     * @param int $nbUsed
     *
     * @return string
     * @throws RoutingException
     */
    public function generate(array $parameters = [], int &$nbUsed = 0): string
    {
        $this->compile();

        $parameters = array_filter($parameters, fn($value) => null !== $value);
        $path =
            (string)preg_replace_callback(
                static::REGEX_ATTRIBUTE,
                function ($matches) use (&$parameters, &$nbUsed) {
                    if (!array_key_exists($matches['name'], $parameters)) {
                        $attribute = $this->getAttribute($matches['name']);

                        if (null !== $attribute && $attribute->hasDefault()) {
                            return $attribute->getDefault();
                        }

                        return $matches[0];
                    }

                    $value = $parameters[$matches['name']];
                    $nbUsed++;
                    unset($parameters[$matches['name']]);

                    return (string)$value;
                },
                $this->getPath()
            );

        // Remove optionals attributes
        $count = 0;
        do {
            $path = preg_replace(
                '~\[[^][]*{' . static::REGEX_ATTRIBUTE_NAME . '}[^][]*\]~',
                '',
                $path,
                -1,
                $count
            );
        } while ($count > 0);

        // Check if missing attribute
        $matches = [];
        if (str_contains($path, '{')) {
            if (preg_match_all(
                    '~{(?<name>' . static::REGEX_ATTRIBUTE_NAME . ')}~',
                    $path,
                    $matches,
                    PREG_PATTERN_ORDER
                ) > 0) {
                throw RoutingException::missingAttributes($matches['name'], $this->getName());
            }
        }

        // Remove brackets
        $count = 0;
        do {
            $path = preg_replace('~\[([^][]*)\]~', '\\1', $path, -1, $count);
        } while ($count > 0);

        // Add over parameters to query string
        if (count($parameters) > 0) {
            $parameters = $this->filterParameters($parameters);
            array_walk_recursive($parameters, fn(&$value) => $value = (string)$value);
            if (!empty($httpBuildQuery = http_build_query($parameters))) {
                $path .= '?' . $httpBuildQuery;
            }
        }

        return $path;
    }

    /**
     * Filter parameters, and remove null parameters.
     *
     * @param array $parameters Parameters
     *
     * @return array
     */
    private function filterParameters(array $parameters): array
    {
        array_walk(
            $parameters,
            function (&$param) {
                if (is_array($param)) {
                    $param = $this->filterParameters($param);
                    $param = array_filter($param, fn($value) => null !== $value);
                }
            }
        );

        return array_filter(
            $parameters,
            function ($value) {
                if (is_array($value)) {
                    return count($value) > 0;
                }

                return null !== $value;
            }
        );
    }

    /**
     * Compile.
     */
    public function compile(): void
    {
        if (null !== $this->path_regex) {
            return;
        }

        // Optional parts
        $path = $this->getPath();
        $count = 0;
        do {
            $path =
                (string)preg_replace(
                    '#\[([^\[\]]+)\]#',
                    '(?:\\1)?',
                    $path,
                    -1,
                    $count
                );
        } while ($count > 0);

        $this->path_regex =
            (string)preg_replace_callback(
                '/{(?<name>' . Route::REGEX_ATTRIBUTE_NAME . ')}/',
                function ($match) {
                    if (null === ($attribute = $this->getAttribute($match['name']))) {
                        return $match[0];
                    }

                    return
                        '(?<' . $attribute->getName() . '>' .
                        ($attribute->getRegex() ?? static::REGEX_ATTRIBUTE_VALUE) . ')';
                },
                $path
            );
    }
}