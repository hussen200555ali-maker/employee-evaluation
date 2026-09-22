<?php
namespace App\Core;

use Exception;

class Router {
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    public static function load($file) {
        $router = new static;
        require $file;
        return $router;
    }

    public function get($uri, $controller) {
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller) {
        $this->routes['POST'][$uri] = $controller;
    }

    public function direct($uri, $requestType) {
      
        // البحث عن تطابق مباشر أولاً
        if (array_key_exists($uri, $this->routes[$requestType])) {
            return $this->callAction(
                ...explode('@', $this->routes[$requestType][$uri])
            );
        }

        // البحث عن تطابق مع متغيرات
        foreach ($this->routes[$requestType] as $route => $controllerAction) {
            // تحويل المسار إلى تعبير نمطي (Regex)
            $routeRegex = preg_replace('/\:([a-zA-Z_]+)/', '(?P<$1>[0-9a-zA-Z_-]+)', $route);
            $routeRegex = '#^' . $routeRegex . '$#';

            if (preg_match($routeRegex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                list($controller, $action) = explode('@', $controllerAction);
                return $this->callAction($controller, $action, $params);
            }
        }

         $errorController = new \App\Controllers\ErrorController();
        return $errorController->notFound($uri);
    }

    protected function callAction($controller, $action, $params = []) {
      
      
      if (!class_exists($controller)) {
            throw new Exception("فئة التحكم {$controller} غير موجودة.");
        }

        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $action)) {
            throw new Exception("الدالة {$action} غير موجودة في {$controller}.");
        }

        return $controllerInstance->$action(...array_values($params));
    }
}
