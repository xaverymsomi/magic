<?php

namespace Core;

use Exception;
use Libs\ApiLog;
use Libs\JWT;
use Libs\ApiLib;
use ReflectionMethod;

class Router
{
    protected static array $routesData = [];
    protected array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => []
    ];
    private $parameters = [];

    public static function load($file): self
    {
        $router = new static;
        require $file;
        self::initModules();
        foreach (self::$routesData as $routesDatum) {
            require $routesDatum;
        }
        return $router;
    }

    public static function initModules()
    {
        $module_directories = [
            'system_modules' => API_BASE_PATH . '/modules/*',
        ];
        $modules = [];
        foreach ($module_directories as $key => $directory) {
            $directories = glob($directory . '/*');

            $modules = array_merge($modules, $directories);
        }
        $module_directories = $modules;

        foreach ($module_directories as $directory) {
            self::processModule($directory);
        }
    }

    public static function processModule($directory)
    {
        // 2. CHECK IF MODULE HAS INNER MODULES AND PROCESS THEM
        if (is_dir($directory)) {
            $module_directories = glob($directory . '/*');
            foreach ($module_directories as $directory) {
                self::processModule($directory);
            }
        } else {
            self::getRoutes($directory);
        }
    }

    public static function getRoutes($directory)
    {
        $fileInfo = pathinfo($directory);
        $filename = $fileInfo['basename'];
        if ($filename == '_routes.php') {
            self::$routesData[] = $directory;
        }
    }

    public function get($uri, $controller, $auth = false)
    {
        $this->routes['GET'][$uri] = ['controller' => $controller, 'auth' => $auth];
    }

    public function post($uri, $controller, $auth = false)
    {
        $this->routes['POST'][$uri] = ['controller' => $controller, 'auth' => $auth];
    }

    public function directs($uri, $requestType)
    {
        //example.com/about/culture
        if (array_key_exists($uri, $this->routes[$requestType])) {
            $route_data = $this->routes[$requestType][$uri]['controller'];
            $route_data[] = $this->routes[$requestType][$uri]['auth'];
            return $this->callAction(
                ...$route_data
            );
        }
        ApiLib::handleResponse('Route not found', [], 404, __METHOD__, 'No route found at ' . __METHOD__);
    }

    protected function callAction($controller, $action, $auth)
    {
        $user_details = null;
        if ($auth) {
            $user_details = $this->checkAuth();
        }

        $controllers = new $controller;
        $controller_class = get_class($controllers);
        $path = explode('\\', $controller_class);
        $dir = implode('/', [$path[1], $path[2]]);

        ApiLog::auditor(['DIR' => $dir, 'NAMESPACE' => $controller_class, 'URL' => Request::uri(), 'REQUEST' => Request::getBody()]);

        ApiLog::sysLog('CALLER: ' . api_user_log($user_details));
        ApiLog::sysLog('API-URL: [' . json_encode(Request::uri()) . ']');
        ApiLog::sysLog('API-REQUEST: [' . json_encode(Request::getBody()) . ']');

        if (!method_exists($controller, $action)) {
            ApiLib::handleResponse('Route not found', [], 404, __METHOD__, "{$controller} does not respond to {$action} action");
        }

        if (sizeof($this->parameters)) {
            $r = new ReflectionMethod($controllers, $action);
            $params = $r->getParameters();
            foreach ($params as $key => $param) {
                $arguments[$key] = $param->getName();
            }
            foreach ($this->parameters as $key => $value) {
                if ($value['name'] !== $arguments[$key]) {
                    ApiLib::handleResponse("Arguments mismatch for {$action} of {$controller}", [], 500, __METHOD__);
                }
                $data[] = $value['value'];
            }
            return $controllers->$action(...$data);
        }
        return $controllers->$action();
    }

    protected function checkAuth()
    {
        $token = JWT::get_bearer_token();
        if (!$token) {
            ApiLib::handleResponse('INVALID SECURITY CREDENTIALS', [], 401, __METHOD__, 'INVALID SECURITY CREDENTIALS at ' . __METHOD__);
        }

        $is_valid_jwt = JWT::is_jwt_valid($token);
        if (!$is_valid_jwt) {
            ApiLib::handleResponse('INVALID AUTHORIZATION DETAILS', [], 401, __METHOD__, 'INVALID AUTHORIZATION DETAILS at ' . __METHOD__);
        }
        return JWT::get_token_data($token);
    }

    public function direct($uri, $requestType)
    {
        ApiLog::sysLog('API-URI: ' . $uri);
        ApiLog::sysLog('API-REQUEST-TYPE: ' . $requestType);

        foreach ($this->routes[$requestType] as $key => $route) {
            $params = [];
            $url_size = explode('/', $key);
            $uri_size = explode('/', $uri);
            if (sizeof($uri_size) == sizeof($url_size)) {
                $indexNum = [];
                $new_index = [];
                $indexNumValues = [];
                foreach ($url_size as $key1 => $item) {
                    if (!preg_match("/{.*}/", $item)) {
                        $indexNum[] = ['key' => $key1, 'value' => $item];
                        $indexNumValues[$key1] = $item;
                    } elseif (preg_match("/{.*}/", $item)) {
                        $params[$key1] = trim($item, '{}');
                    }
                }

                $tmp = [];

                if ($params) {
                    $id = 0;
                    foreach ($uri_size as $key2 => $item) {
                        if (isset($indexNumValues[$key2])) {
                            if ($item == $indexNumValues[$key2]) {
                                $new_index[$key2] = $item;
                            }
                        } else {
                            $id = $key2;
                        }
                    }

                    if (isset($params[$id])) {
                        $tmp[] = ['name' => $params[$id], 'value' => $uri_size[$id]];
                    }

                    if (implode('/', $indexNumValues) == implode('/', $new_index)) {
                        $uri = $key;
                        $this->parameters = $tmp;
                        break;
                    } else {
                        $this->parameters = [];
                    }
                }
            }
        }

        if (array_key_exists($uri, $this->routes[$requestType])) {
            // explode('@',$this->routes[$requestType][$uri]);
            $route_data = $this->routes[$requestType][$uri]['controller'];
            $route_data[] = $this->routes[$requestType][$uri]['auth'];
            return $this->callAction(
                ...$route_data
            );
        }

        ApiLib::handleResponse('No route found', [], 404, __METHOD__);
    }

    protected function callActions($controller, $action)
    {
        // die(var_dump($controller,$action));
        $controllerObj = new $controller;
        if (!method_exists($controllerObj, $action)) {
            ApiLib::handleResponse("{$controller} does not respond to {$action} action", [], 404, __METHOD__);
        }
        $arguments = [];
        $data = [];

        if (sizeof($this->parameters)) {
            $r = new ReflectionMethod($controllerObj, $action);
            $params = $r->getParameters();
            foreach ($params as $key => $param) {
                $arguments[$key] = $param->getName();
            }
            foreach ($this->parameters as $key => $value) {
                if ($value['name'] !== $arguments[$key]) {
                    ApiLib::handleResponse("Arguments mismatch for {$action} of {$controller}", [], 404, __METHOD__);
                }
                $data[] = $value['value'];
            }
            return $controllerObj->$action(...$data);
        }
        return $controllerObj->$action();
    }
}