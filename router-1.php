<?php

class Router
{
    protected $routes = [];
    protected $middleware = [];

    public function addRoute($method, $uri, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function addMiddleware($name, $handler)
    {
        $this->middleware[$name] = $handler;
    }

    public function run($uri, $method)
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $method = strtolower($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['uri'], $uri, $matches)) {
                continue;
            }

            $handler = $route['handler'];
            $middleware = $route['middleware'];

            if ($middleware) {
                foreach ($middleware as $name) {
                    if (isset($this->middleware[$name])) {
                        $handler = $this->middleware[$name]($handler);
                    }
                }
            }

            return $handler($uri, $matches);
        }

        throw new Exception('No route found for the specified URI and method.');
    }
}


$router = new Router();

$router->addRoute('GET', '/', function ($uri, $matches) {
    require 'controllers/index.php';
});

$router->addRoute('GET', '/about', function ($uri, $matches) {
    require 'controllers/about.php';
}, ['auth']);

$router->addRoute('GET', '/contact', function ($uri, $matches) {
    require 'controllers/contact.php';
});

$router->addMiddleware('auth', function ($handler) {
    return function ($uri, $matches) use ($handler) {
        if (!isAuthenticated()) {
            require 'controllers/401.php';
            return;
        }

        return $handler($uri, $matches);
    };
});

try {
    $router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
}catch(Exception $err){ 
    echo $err->getMessage()
   }
