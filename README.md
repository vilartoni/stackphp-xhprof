Xhprof middleware for StackPHP
==============================

This package contains a [StackPHP](http://stackphp.com/) middleware that activates XHProf profiling
by leveraging the [lox/xhprof](https://github.com/lox/xhprof) library.

Just by sending `xhprof=1` on the `GET` request, `XhprofMiddleware` will generate the profiling for the
requested URL and append a link to the results to the response.

Requirements
------------
`xhprof` extension must be loaded. Otherwise an exception will be thrown.

Options
-------

The `XhprofMiddleware` accepts an array of options:

- **output_dir**: the directory used to store XHProf runs.

Example
-------

```php
<?php

use Avs\Stack\XhprofMiddleware;

require_once __DIR__ . '../vendor/autoload.php';

$app = new Silex\Application();

$stack = (new Stack\Builder())
    ->push(XhprofMiddleware::class, '/secret/xhprof');

$app = $stack->resolve($app);

$request = Request::createFromGlobals();
$response = $app->handle($request)->send();

$app->terminate($request, $response);
```

Installation
------------

The recommended way to install `XhprofMiddleware` is through [Composer](http://getcomposer.org/):

``` json
{
    "require": {
        "vilartoni/stackphp-xhprof": "dev-master"
    }
}
```

**Note:** as this package depends on `lox/xhprof` which is in `dev` stability, you may need to
allow it explicitly in case you're not already using it.

```json
{
    "require": {
        "vilartoni/stackphp-xhprof": "dev-master",
        "lox/xhprof": "@dev"
    }
}

```
