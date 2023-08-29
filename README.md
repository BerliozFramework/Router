# Berlioz Router

[![Latest Version](https://img.shields.io/packagist/v/berlioz/router.svg?style=flat-square)](https://github.com/BerliozFramework/Router/releases)
[![Software license](https://img.shields.io/github/license/BerliozFramework/Router.svg?style=flat-square)](https://github.com/BerliozFramework/Router/blob/2.x/LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/BerliozFramework/Router/tests.yml?branch=2.x&style=flat-square)](https://github.com/BerliozFramework/Router/actions/workflows/tests.yml?query=branch%3A2.x)
[![Quality Grade](https://img.shields.io/codacy/grade/698b7941569c4926b67bab59efbcfafd/2.x.svg?style=flat-square)](https://www.codacy.com/manual/BerliozFramework/Router)
[![Total Downloads](https://img.shields.io/packagist/dt/berlioz/router.svg?style=flat-square)](https://packagist.org/packages/berlioz/router)

**Berlioz Router** is a PHP library for manage HTTP routes, respecting PSR-7 (HTTP message interfaces) standard.

## Installation

### Composer

You can install **Berlioz Router** with [Composer](https://getcomposer.org/), it's the recommended installation.

```bash
$ composer require berlioz/router
```

### Dependencies

* **PHP** ^8.0
* Packages:
    * **berlioz/http-message**
    * **psr/log**

## Usage

### Routes

#### Create route

You can create simple route like this:

```php
use Berlioz\Router\Route;

$route = new Route('/path-of/my-route');
$route = new Route('/path-of/my-route/{attribute}/with-attribute');
```

Constructor arguments are:

- **defaults**: an associated array to set default values of attributes when route is generated
- **requirements**: an associated array to restrict the format of attributes. Key is the name of attribute and value is
  the validation regex
- **name**: name of route
- **method**: an array of allowed HTTP methods, or just a method
- **host**: an array of allowed hosts, or just a host
- **priority**: you can specify the priority for a route (default: -1)

#### Route group

A route can be transformed to a group, only associate another route to them.

```php
use Berlioz\Router\Route;

$route = new Route('/path');
$route->addRoute($route2 = new Route('/path2')); // Path will be: /path/path2
```

Children routes inherit parent route attributes, requirements, ...

#### Attributes

Route accept optional attributes, you need to wrap the optional part by brackets.

```php
$route = new \Berlioz\Router\Route('/path[/optional-part/{with-attribute}]');
```

You can also define requirements directly in the path :

- Add a regular expression after the name of attribute (separate by ":").
- Add a type name after the name of attribute (separate by "::").

```php
$route = new \Berlioz\Router\Route('/path/{attributeName:\d+}');
$route = new \Berlioz\Router\Route('/path/{attributeName::int}');
```

Supported defined types:

- `int` (equivalent of `\d+`)
- `float` (equivalent of `\d+(\.\d+)`)
- `uuid4` (equivalent of `[0-9A-Fa-f]{8}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{12}`)
- `slug` (equivalent of `[a-z0-9]+(?:-[a-z0-9]+)*`)
- `md5` (equivalent of `[0-9a-fA-F]{32}`)
- `sha1` (equivalent of `[0-9a-fA-F]{40}`)
- `domain` (equivalent of `([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}`)

### Router

The router is the main functionality in the package, it is defined by `Router` class. He is able to find the
good `Route` object according to a `ServerRequestInterface` object (see PSR-7).

```php
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Router\Route;
use Berlioz\Router\Router;

// Create server request or get them from another place in your app
$serverRequest = new ServerRequest(...);

// Create router
$router = new Router();
$router->addRoute(
    new Route('/path-of/my-route'),
    new Route('/path-of/my-route/{attribute}/with-attribute')
);

$route = $router->handle($serverRequest);
```

#### Generate path

You can generate a path with some parameters directly with `Router` object.

```php
use Berlioz\Router\Exception\NotFoundException;
use Berlioz\Router\Router;

$router = new Router();
// ...add routes

try {
    $path = $router->generate('name-of-route', ['attribute1' => 'value']);
} catch (NotFoundException $exception) {
    // ... not found route
}
```

The return of method, is the path in string format or thrown an exception if not able to generate path (not all required
parameters for example).

#### Valid path

You can be valid a `ServerRequestInterface` to known if a path can be treated by a route.

```php
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Router\Router;

$serverRequest = new ServerRequest(...);
$router = new Router();
// ...add routes

/** bool $valid Valid path ?*/
$valid = $router->isValid($serverRequest);
```

The return of method is a `boolean` value.