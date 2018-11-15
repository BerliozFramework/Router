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

use Berlioz\PhpDoc\DocBlock;
use Berlioz\PhpDoc\PhpDocFactory;
use Berlioz\Router\Exception\RoutingException;
use Psr\SimpleCache\CacheException;

class RouteGenerator
{
    /** @var \Berlioz\PhpDoc\PhpDocFactory */
    private $phpDocFactory;

    /**
     * RouteGenerator constructor.
     *
     * @param \Berlioz\PhpDoc\PhpDocFactory|null $phpDocFactory
     */
    public function __construct(?PhpDocFactory $phpDocFactory = null)
    {
        $this->phpDocFactory = $phpDocFactory;
    }

    /**
     * Get PhpDocFactory object to read phpDoc.
     *
     * @return \Berlioz\PhpDoc\PhpDocFactory
     */
    public function getPhpDocFactory(): PhpDocFactory
    {
        if (is_null($this->phpDocFactory)) {
            $this->phpDocFactory = new PhpDocFactory;
        }

        return $this->phpDocFactory;
    }

    /**
     * Generate routes from class name.
     *
     * @param string $class
     * @param string $basePath
     * @param array  $context
     *
     * @return \Berlioz\Router\RouteSetInterface
     * @throws \Berlioz\Router\Exception\RoutingException
     * @throws \Psr\SimpleCache\CacheException
     */
    public function fromClass(string $class, string $basePath = '', array $context = []): RouteSetInterface
    {
        try {
            $routeSet = new RouteSet;

            $class = ltrim($class, '\\');
            $docs = $this->getPhpDocFactory()->getClassDocs($class);
            $docs =
                array_filter($docs,
                    function (DocBlock $doc) {
                        return $doc->hasTag('route');
                    });

            if (count($docs) > 0) {
                $classDoc = $docs[$class] ?? null;

                // Check class doc constraints
                if ($classDoc instanceof DocBlock\ClassDocBlock && count($classDoc->getTag('route')) > 1) {
                    throw new RoutingException(sprintf('Controller "%s" must not declare @route annotation more than one time in PhpDoc of class', $class));
                }

                foreach ($docs as $doc) {
                    if ($doc instanceof DocBlock\MethodDocBlock) {
                        // Filter public methods
                        if ($doc->isPublic() &&
                            !$doc->isConstructor() &&
                            !$doc->isConstructor() &&
                            !$doc->isAbstract() &&
                            !$doc->isStatic()) {
                            $routeSet->merge($this->fromDocBlock($doc, $classDoc, $basePath, $context));
                        }
                    }
                }
            }

            return $routeSet;
        } catch (CacheException $e) {
            throw $e;
        } catch (RoutingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RoutingException(sprintf('Unable to parse routes from class "%s"', $class), 0, $e);
        }
    }

    /**
     * Generate routes from DocBlock objects.
     *
     * @param \Berlioz\PhpDoc\DocBlock\AbstractFunctionDocBlock $docBlock
     * @param \Berlioz\PhpDoc\DocBlock|null                     $baseDocBlock
     * @param string                                            $basePath
     * @param array                                             $context
     *
     * @return \Berlioz\Router\RouteSetInterface
     * @throws \Berlioz\Router\Exception\RoutingException
     */
    protected function fromDocBlock(DocBlock\AbstractFunctionDocBlock $docBlock, ?DocBlock $baseDocBlock = null, string $basePath = '', array $context = []): RouteSetInterface
    {
        $routeSet = new RouteSet;

        // Define context
        if ($docBlock instanceof DocBlock\MethodDocBlock) {
            $context = array_replace($context,
                                     ['_class'  => $docBlock->getClassName(),
                                      '_method' => $docBlock->getShortName()]);
        } else {
            $context = array_replace($context,
                                     ['_function' => $docBlock->getName()]);
        }

        // Base path and options
        $baseOptions = [];
        if (!is_null($baseDocBlock) && ($baseTag = $baseDocBlock->getTag('route'))) {
            $baseTag = reset($baseTag);
            $baseTagValue = $baseTag->getValue();

            if (is_array($baseTagValue)) {
                if ((isset($baseTagValue['path']) && is_string($baseTagValue['path']) && $pathKey = 'path') ||
                    (isset($baseTagValue[0]) && is_string($baseTagValue[0])) && !$pathKey = 0) {
                    $basePath = sprintf('/%s/%s', ltrim($basePath, '/'), ltrim($baseTagValue[$pathKey], '/'));
                    $basePath = sprintf('/%s', ltrim($basePath, '/'));
                    unset($baseTagValue[$pathKey]);
                }

                $baseOptions = $baseTagValue;
            } else {
                throw new RoutingException(sprintf('Parse error of @route annotation: "@route %s"', $baseTag->getRaw()));
            }
        }

        foreach ($docBlock->getTag('route') as $tag) {
            $tagValue = $tag->getValue();

            if (is_array($tagValue)) {
                if ((isset($tagValue['path']) && is_string($tagValue['path']) && $pathKey = 'path') ||
                    (isset($tagValue[0]) && is_string($tagValue[0])) && !$pathKey = 0) {
                    // Get path and add base path
                    $path = $tagValue[$pathKey];
                    unset($tagValue[$pathKey]);
                    $path = sprintf('/%s/%s', ltrim($basePath, '/'), ltrim($path, '/'));
                    $path = sprintf('/%s', ltrim($path, '/'));

                    // Create route
                    $routeSet->addRoute(new Route($path,
                                                  json_decode(json_encode(array_replace($baseOptions, $tagValue)), true),
                                                  $context));
                } else {
                    throw new RoutingException(sprintf('Path not found in @route annotation: "@route %s"', $tag->getRaw()));
                }
            } else {
                throw new RoutingException(sprintf('Parse error of @route annotation: "@route %s"', $tag->getRaw()));
            }
        }

        return $routeSet;
    }
}