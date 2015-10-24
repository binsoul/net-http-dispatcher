# net-http-dispatcher

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

This package allows to map arbitrary names to dispatchable objects using named parameters provided in a context array. Dispatchable objects are then invoked to return a response. They can be surrounded by middleware to alter the incoming request or the outgoing response.  

## Install

Via composer:

``` bash
$ composer require binsoul/net-http-dispatcher
```

## Responder

A responder can be a closure, an object with an __invoke method or an object with multiple methods. It can receive the request object, the context object or any value from the context as parameters and it has to return a response. 

The dispatcher resolves parameters via reflection. If a parameter cannot be resolved and is not optional an exception is thrown.

For example the responder "blog.edit" will receive the following parameters: 
``` php
$dispatcher->addResponder(
    'blog.edit',
    function ($id, RequestInterface $request, Context $context, $optional = 'foo')
    {
        // $id = 1
        // $request = object
        // $context = object
        // $optional = 'foo'
        
        return new Response(...);
    }
);

$dispatcher->handle($request, ['responder' => 'blog.edit', 'id' => 1]);
```


## Middleware

Middleware can be a closure or an object with an __invoke method. It has to accept a request, a context and the next middleware as parameters and it has to return a response.

For example middleware may or may not perform the various optional processes:
``` php
$middleware = function (RequestInterface $request, Context $context, callable $next) {
    // optionally return a response early
    if (...) {
        return new Response(...);
    }

    // optionally modify the request
    $request = $request->withUri(..);

    // optionally modify the context
    $context = $context->withParameter(...);

    // invoke the $next middleware
    $response = $next($request, $context);

    // optionally modify the response
    $response = $response->withStatus(...);

    // always return a response
    return $response;
};
```

## Dispatcher

A dispatcher uses the information provided by a context array to find a responder. The default keys are "responder" to indicate which responder should be invoked and "method" to indicate which method of the responder should be called.  

The used array keys can be configured. The following example maps the reponder to the array key "controller" and the method to "action": 
``` php
class BlogController
{
    public function index()
    {
        return new Response(...);
    }
}

$dispatcher->addResponder('blog', new BlogController());

$dispatcher->defineParameters('controller', 'action');

// will invoke BlogController->index()
$dispatcher->handle($request, ['controller' => 'blog', 'action' => 'index']); 
```

Responders can be registered either as closures or concrete objects or as strings. If a responder is registered as a string the provided factory is used to lazily build responders.
``` php
$dispatcher->addResponder('blog', 'BlogResponder');
$dispatcher->setFactory($factory);

// will call $factory->buildResponder('BlogResponder');
$dispatcher->handle($request, ['responder' => 'blog']); 
```

The same applies to registering middlewares.
``` php
$dispatcher->addMiddleware('AuthMiddleware')
$dispatcher->setFactory($factory);

// will call $factory->buildMiddleware('AuthMiddleware');
$dispatcher->handle($request, ['responder' => 'blog']); 
```

Middleware is added to an internal queue which surrounds the final call to the responder.

For example if the queue is build like:
``` php
$dispatcher->addResponder('blog', 'BlogResponder');

$dispatcher->addMiddleware('Foo');
$dispatcher->addMiddleware('Bar');
$dispatcher->addMiddleware('Baz');

$dispatcher->handle($request, ['controller' => 'blog']); 
```

The request and response path through the middlewares will look like this:
``` text
Foo is 1st on the way in
    Bar is 2nd on the way in
        Baz is 3rd on the way in
            BlogResponder is invoked        
        Baz is 1st on the way out
    Bar is 2nd on the way out
Foo is 3rd on the way out
```

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/binsoul/net-http-dispatcher.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/binsoul/net-http-dispatcher.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/binsoul/net-http-dispatcher
[link-downloads]: https://packagist.org/packages/binsoul/net-http-dispatcher
[link-author]: https://github.com/binsoul
