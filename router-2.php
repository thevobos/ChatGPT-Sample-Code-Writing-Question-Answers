<?php

class Router
{
    protected $routes = [];
    protected $middleware = [];

    public function get($uri, $controller, $middleware = [])
    {
        $this->routes['GET'][$uri] = $controller;
        $this->middleware['GET'][$uri] = $middleware;
    }

    public function post($uri, $controller, $middleware = [])
    {
        $this->routes['POST'][$uri] = $controller;
        $this->middleware['POST'][$uri] = $middleware;
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if (array_key_exists($method, $this->routes)) {
            foreach ($this->routes[$method] as $route => $controller) {
                if (preg_match("#^$route$#", $uri, $params)) {
                    array_shift($params);

                    $middleware = $this->middleware[$method][$route];
                    $this->executeMiddleware($middleware);

                    return $this->executeController($controller, $params);
                }
            }
        }

        return $this->executeController('controllers/404.php');
    }

    protected function executeMiddleware($middleware)
    {
        foreach ($middleware as $mw) {
            require $mw;
        }
    }

    protected function executeController($controller, $params = [])
    {
        $controller = explode('@', $controller);
        $controllerPath = $controller[0];
        $controllerMethod = $controller[1];

        require $controllerPath;

        $className = basename($controllerPath, '.php');
        $controller = new $className();

        return call_user_func_array([$controller, $controllerMethod], $params);
    }
}



$router = new Router();

$router->get('/', 'HomeController@index', ['AuthMiddleware']);
$router->post('/login', 'AuthController@login');

$router->run();
