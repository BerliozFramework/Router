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


use Berlioz\Router\Exception\RoutingException;
use phpDocumentor\Reflection\DocBlockFactory;

class RouteGenerator
{
    /** @var \phpDocumentor\Reflection\DocBlockFactory */
    private static $docBlockFactory;

    /**
     * Get DockBlockFactory object to read doc block.
     *
     * @return \phpDocumentor\Reflection\DocBlockFactory
     */
    private function getDocBlockFactory(): DocBlockFactory
    {
        if (is_null(self::$docBlockFactory)) {
            self::$docBlockFactory = DocBlockFactory::createInstance();
        }

        return self::$docBlockFactory;
    }

    /**
     * Generate routes from class name.
     *
     * @param string $className
     * @param string $basePath
     * @param array  $context
     *
     * @return \Berlioz\Router\RouteSetInterface
     * @throws \Berlioz\Router\Exception\RoutingException
     */
    public function parseClass(string $className, string $basePath = '', array $context = []): RouteSetInterface
    {
        $routeSet = new RouteSet;

        try {
            // Do the reflection of class, create the object and do the mapping
            if (class_exists($className)) {
                $reflectionClass = new \ReflectionClass($className);

                // Get all public methods of class
                $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    $routeSet->merge($this->fromReflectionFunction($method, $basePath, $context));
                }
            } else {
                throw new RoutingException(sprintf('Class "%s" doesn\'t exists', $className));
            }
        } catch (\Exception $e) {
            throw new RoutingException(sprintf('Unable to generate routes from class "%s"', $className), 0, $e);
        }

        return $routeSet;
    }

    /**
     * Generate route from function.
     *
     * @param \ReflectionFunctionAbstract $reflectionFunction
     * @param string                      $basePath
     * @param array                       $context
     *
     * @return \Berlioz\Router\RouteSetInterface
     * @throws \Berlioz\Router\Exception\RoutingException
     */
    protected function fromReflectionFunction(\ReflectionFunctionAbstract $reflectionFunction, string $basePath = '', array $context = []): RouteSetInterface
    {
        $routeSet = new RouteSet;

        try {
            if (!($reflectionFunction instanceof \ReflectionMethod) || $reflectionFunction->isPublic()) {
                if ($functionDoc = $reflectionFunction->getDocComment()) {
                    $docBlock = $this->getDocBlockFactory()->create($functionDoc);

                    if ($docBlock->hasTag('route')) {
                        $context = array_replace($context,
                                                 ['_class'  => $reflectionFunction->class,
                                                  '_method' => $reflectionFunction->name]);

                        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Generic $tag */
                        foreach ($docBlock->getTagsByName('route') as $tag) {
                            $routeSet->addRoute($this->fromAnnotation($tag->getDescription()->render(), $basePath, $context));
                        }
                    }
                }
            } else {
                /** @var \ReflectionMethod $reflectionFunction */
                throw new RoutingException('Method given, must be public');
            }
        } catch (\Exception $e) {
            if ($reflectionFunction instanceof \ReflectionMethod) {
                throw new RoutingException(sprintf('Method "%s::%s" route error: %s', $reflectionFunction->class, $reflectionFunction->getName(), $e->getMessage()));
            } else {
                throw new RoutingException(sprintf('Function "%s" route error: %s', $reflectionFunction->getName(), $e->getMessage()));
            }
        }

        return $routeSet;
    }

    /**
     * Generate route from annotation.
     *
     * @param string $annotation
     * @param string $basePath
     * @param array  $context
     *
     * @return \Berlioz\Router\RouteInterface
     * @throws \Berlioz\Router\Exception\RoutingException
     */
    protected function fromAnnotation(string $annotation, string $basePath = '', array $context = []): RouteInterface
    {
        try {
            $regex_define = <<<'EOD'
(?(DEFINE)
    (?<d_quotes> \'(?>[^'\\]++|\\.)*\' | "(?>[^"\\]++|\\.)*" )
    (?<d_json_element> (?: \g<d_quotes> | [\w_]+ ) \s* : \s* \g<d_quotes> )
    (?<d_json> { \s* \g<d_json_element> (?: \s* , \s* \g<d_json_element> )* \s* } )
    (?<d_bool> true | false )
    (?<d_option> [\w_]+\s*=\s*(?: \g<d_json> | \g<d_quotes> | \g<d_bool> ) )
)
EOD;

            $matches = [];
            if (preg_match('~' . $regex_define . '^ \( \s* (?<route> \g<d_quotes> ) (?<options> (?: \s* , \s* \g<d_option> )+ )? \s* \) $ ~x', $annotation, $matches) == 1) {
                // Treatment for base of path
                if (!empty($basePath)) {
                    // Remove slash at the end of base path
                    if (substr($basePath, -1) == '/') {
                        $basePath = substr($basePath, 0, -1);
                    }
                }

                // Route
                $routePath = ($basePath . substr($matches['route'], 1, -1));

                // Options
                $routeOptions = [];
                if (!empty($matches['options'])) {
                    $matchesOptions = [];
                    if (preg_match_all('~' . $regex_define . '\s* , \s* (?<name> [\w_]+) \s* = \s* (?: (?<json> \g<d_json> ) | (?<bool> \g<d_bool> ) | (?<string> \g<d_quotes> ) ) \s* ~x', $matches['options'], $matchesOptions, PREG_SET_ORDER)) {
                        foreach ($matchesOptions as $matchOption) {
                            $optionName = $matchOption['name'];

                            if (!empty($matchOption['json'])) {
                                $optionValue = json_decode(addcslashes($matchOption['json'], '\\'), true);

                                if ($optionValue !== false) {
                                    if (!isset($routeOptions[$optionName])) {
                                        $routeOptions[$optionName] = $optionValue;
                                    } else {
                                        $routeOptions[$optionName] = array_merge($routeOptions[$optionName], $optionValue);
                                    }
                                } else {
                                    throw new RoutingException(sprintf('Invalid option format for "%s"', $optionName));
                                }
                            } else {
                                if (!empty($matchOption['bool'])) {
                                    $routeOptions[$optionName] = $matchOption['bool'] == true;
                                } else {
                                    if (!empty($matchOption['string'])) {
                                        $routeOptions[$optionName] = substr($matchOption['string'], 1, -1);
                                    }
                                }
                            }
                        }
                    }
                }

                return new Route($routePath, $routeOptions, $context);
            } else {
                throw new RoutingException('Invalid regex');
            }
        } catch (\Exception $e) {
            throw new RoutingException(sprintf('Parse error of @route annotation, "%s"', $annotation), 0, $e);
        }
    }
}