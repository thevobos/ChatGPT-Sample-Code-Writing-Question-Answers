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
