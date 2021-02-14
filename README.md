# Berlioz Router

[![Latest Version](https://img.shields.io/packagist/v/berlioz/router.svg?style=flat-square)](https://github.com/BerliozFramework/Router/releases)
[![Software license](https://img.shields.io/github/license/BerliozFramework/Router.svg?style=flat-square)](https://github.com/BerliozFramework/Router/blob/1.x/LICENSE)
[![Build Status](https://img.shields.io/travis/com/BerliozFramework/Router/1.x.svg?style=flat-square)](https://travis-ci.com/BerliozFramework/Router)
[![Quality Grade](https://img.shields.io/codacy/grade/698b7941569c4926b67bab59efbcfafd/1.x.svg?style=flat-square)](https://www.codacy.com/manual/BerliozFramework/Router)
[![Total Downloads](https://img.shields.io/packagist/dt/berlioz/router.svg?style=flat-square)](https://packagist.org/packages/berlioz/router)

**Berlioz Router** is a PHP library for manage HTTP routes, respecting PSR-7 (HTTP message interfaces) standard.

## Installation

### Composer

You can install **Berlioz Router** with [Composer](https://getcomposer.org/), it's the recommended installation.

```bash
$ composer require berlioz/router
```

### Dependencies

* **PHP** ^7.1 || ^8.0
* Packages:
  * **berlioz/http-message**
  * **berlioz/php-doc**
  * **psr/log**


## Usage

### Create route

You can create simple route like this:
```php
$route = new Route('/path-of/my-route');
$route = new Route('/path-of/my-route/{attribute}/with-attribute');
```

Second parameter of constructor is an array of options.
All available options are:
* **requirements**: associated array to restrict format of attributes. Key is the name of attribute and value is the validation regex.
* **defaults**: associated array to set default values of attributes when route is generated.
* **priority**: you can specify the priority for a route (default: -1).

### Associate routes to a route set

Route set is managed by class `RouteSet` class:
```php
$routeSet = new RouteSet;
$routeSet->addRoute(new Route('/path/of/my/route'));
```

You can also merge two route set together:
```php
$routeSet = new RouteSet;
$routeSet->addRoute(new Route('/path/of/my/route'));

$secondRouteSet = new RouteSet;
$secondRouteSet->addRoute(new Route('/path/of/my/second/route'));

$routeSet->merge($secondRouteSet);
```

### Uses router

The router is the main functionality in the package, it is defined by `Router` class.
He is able to find the good `Route` object according to a `ServerRequestInterface` object (see PSR-7).

```php
// Create server request or get them from another place in your app
$serverRequest = new ServerRequest(...);

// Create route set
$routeSet = new RouteSet;
$routeSet->addRoute(new Route('/path-of/my-route'));
$routeSet->addRoute(new Route('/path-of/my-route/{attribute}/with-attribute'));

$router = new Router;
$router->setRouteSet($routeSet);
$route = $router->handle($serverRequest);
```

You can retrieve extracted attributes of path in `ServerRequestInterface` object stored in `Router` object:

```php
/** array $attributes Associated array with key/value pairs */ 
$attributes = $router->getServerRequest()->getAttributes();
```

#### Generate path

You can generate a path with some parameters directly with `Router` object.

```php
$router = new Router;
...
/** string|false $path Generated path */
$path = $router->generate('name-of-route', ['attribute1' => 'value']);
```

The return of method, is the path in string format or `false` value if not able to generate path (not all required parameters for example).

#### Valid path

You can valid a path to known if a path can be treated by a route.

```php
$router = new Router;
...
/** bool $valid Valid path ?*/
$valid = $router->isValid('/my-path/path/file');
```

The return of method is a `boolean` value.

### Parse routes from controllers or classes

You can parse routes from your controllers with PhpDoc annotations.

#### Example of PhpDoc

```php
class Controller
{
    /**
     * My controller method.
     *
     * @route("/path/{attribute}/path", name="method")
     */
    public function myMethod()
    {
        ...
    }
}
```

Like you see, it's possible to add options to the annotation, like *name* in example.
Options need to be separated by comma and the value must be a quoted string or a valid JSON format.
All options accepted by `Route` object are available here.

#### How to generate route set from class

A `RouteGenerator` class is available to generate routes from classes.

```php
$generator = new RouteGenerator;
$routeSet = $generator->fromClass(Controller::class);

$router = new Router;
$router->addRouteSet($routeSet);
```