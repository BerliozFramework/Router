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
use Berlioz\PhpDoc\DocBlock\AbstractFunctionDocBlock;
use Berlioz\PhpDoc\Exception\PhpDocException;
use Berlioz\PhpDoc\PhpDocFactory;
use Berlioz\Router\Exception\RoutingException;
use Psr\SimpleCache\CacheException;

/**
 * Class RouteGenerator.
 *
 * @package Berlioz\Router
 */
class RouteGenerator
{
    /** @var PhpDocFactory */
    private $phpDocFactory;

    /**
     * RouteGenerator constructor.
     *
     * @param PhpDocFactory|null $phpDocFactory
     */
    public function __construct(?PhpDocFactory $phpDocFactory = null)
    {
        $this->phpDocFactory = $phpDocFactory;
    }

    /**
     * Get PhpDocFactory object to read phpDoc.
     *
     * @return PhpDocFactory
     */
    public function getPhpDocFactory(): PhpDocFactory
    {
        if (null === $this->phpDocFactory) {
            $this->phpDocFactory = new PhpDocFactory();
        }

        return $this->phpDocFactory;
    }

    /**
     * Generate routes from class name.
     *
     * @param string $class
     * @param string $basePath
     * @param array $context
     *
     * @return RouteSetInterface
     * @throws RoutingException
     * @throws CacheException
     */
    public function fromClass(string $class, string $basePath = '', array $context = []): RouteSetInterface
    {
        try {
            $routeSet = new RouteSet();

            $class = ltrim($class, '\\');
            $docs = $this->getPhpDocFactory()->getClassDocs($class);
            $docs =
                array_filter(
                    $docs,
                    function (DocBlock $doc) {
                        return $doc->hasTag('route');
                    }
                );

            if (count($docs) === 0) {
                return $routeSet;
            }

            $classDoc = $docs[$class] ?? null;

            // Check class doc constraints
            if ($classDoc instanceof DocBlock\ClassDocBlock && count($classDoc->getTag('route')) > 1) {
                throw new RoutingException(
                    sprintf(
                        'Controller "%s" must not declare @route annotation more than one time in PhpDoc of class',
                        $class
                    )
                );
            }

            foreach ($docs as $doc) {
                if (!$doc instanceof DocBlock\MethodDocBlock) {
                    continue;
                }

                // Filter public methods
                if ($doc->isPublic() && !$doc->isConstructor() && !$doc->isAbstract() && !$doc->isStatic()) {
                    $routeSet->merge($this->fromDocBlock($doc, $classDoc, $basePath, $context));
                }
            }

            return $routeSet;
        } catch (PhpDocException $e) {
            throw new RoutingException(sprintf('Unable to parse routes from class "%s"', $class), 0, $e);
        }
    }

    /**
     * Generate routes from DocBlock objects.
     *
     * @param AbstractFunctionDocBlock $docBlock
     * @param DocBlock|null $baseDocBlock
     * @param string $basePath
     * @param array $context
     *
     * @return RouteSetInterface
     * @throws RoutingException
     */
    protected function fromDocBlock(
        AbstractFunctionDocBlock $docBlock,
        ?DocBlock $baseDocBlock = null,
        string $basePath = '',
        array $context = []
    ): RouteSetInterface {
        $routeSet = new RouteSet();

        // Define context
        if ($docBlock instanceof DocBlock\MethodDocBlock) {
            $context = array_replace(
                $context,
                [
                    '_class' => $docBlock->getClassName(),
                    '_method' => $docBlock->getShortName()
                ]
            );
        } else {
            $context = array_replace(
                $context,
                ['_function' => $docBlock->getName()]
            );
        }

        // Base path and options
        $baseOptions = [];
        if (null !== $baseDocBlock && ($baseTag = $baseDocBlock->getTag('route'))) {
            $baseTag = reset($baseTag);
            $baseTagValue = $baseTag->getValue();

            if (!is_array($baseTagValue)) {
                throw new RoutingException(
                    sprintf('Parse error of @route annotation: "@route %s"', $baseTag->getRaw())
                );
            }

            if ((isset($baseTagValue['path']) && is_string($baseTagValue['path']) && $pathKey = 'path') ||
                (isset($baseTagValue[0]) && is_string($baseTagValue[0])) && !$pathKey = 0) {
                $basePath = sprintf('/%s/%s', ltrim($basePath, '/'), ltrim($baseTagValue[$pathKey], '/'));
                $basePath = sprintf('/%s', ltrim($basePath, '/'));
                unset($baseTagValue[$pathKey]);
            }

            $baseOptions = $baseTagValue;
        }

        foreach ($docBlock->getTag('route') as $tag) {
            $tagValue = $tag->getValue();

            if (!is_array($tagValue)) {
                throw new RoutingException(sprintf('Parse error of @route annotation: "@route %s"', $tag->getRaw()));
            }

            $pathKey = 'path';
            if (!isset($tagValue['path']) || !is_string($tagValue['path'])) {
                $pathKey = 0;

                if (!isset($tagValue[0]) || !is_string($tagValue[0])) {
                    throw new RoutingException(
                        sprintf('Path not found in @route annotation: "@route %s"', $tag->getRaw())
                    );
                }
            }

            // Get path and add base path
            $path = $tagValue[$pathKey];
            unset($tagValue[$pathKey]);
            $path = sprintf('/%s/%s', ltrim($basePath, '/'), ltrim($path, '/'));
            $path = sprintf('/%s', ltrim($path, '/'));

            // Create route
            $routeSet->addRoute(
                new Route(
                    $path,
                    json_decode(json_encode(array_replace($baseOptions, $tagValue)), true),
                    $context
                )
            );
        }

        return $routeSet;
    }
}