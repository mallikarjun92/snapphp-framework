<?php

namespace SnapPHP\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\AnnotationReader;
use SnapPHP\Core\Annotations\Route;
use SnapPHP\Core\Debug;
use ReflectionClass;

class Kernel
{
    protected $router;
    protected $debug;

    public function boot()
    {
        // Initialize the router
        $this->router = new Router();

        // Initialize the debug
        $this->debug = new Debug();
        
        // Initialize the global constants
        define('BASE_PATH', dirname(__DIR__, 4));

        //TODO: add a mechanism to load env / configs


        // Load routes by scanning the controllers
        $this->loadRoutesFromControllers();
    }

    /**
     * Handles the request and returns a response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request, $this->debug);
    }

    /**
     * Terminate the request/response lifecycle.
     *
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $response->send();
        $this->debug->render(); // Render debug information at the end
    }

    /**
     * Scan controllers and load routes from annotations.
     */
    protected function loadRoutesFromControllers()
    {
        // Define the directory where controllers are located
        $controllerDir = BASE_PATH . '/app/controllers';
        
        // Get all PHP files in the controller directory
        $controllerFiles = glob($controllerDir . '/*.php');

        foreach ($controllerFiles as $file) {
            require_once $file;
            $className = 'App\\Controllers\\' . basename($file, '.php');
            
            // Check if the class exists
            if (class_exists($className)) {
                $this->registerRoutesFromAnnotations($className);
            }
        }
    }

    /**
     * Register routes defined by annotations in a controller.
     *
     * @param string $className
     */
    protected function registerRoutesFromAnnotations($className)
    {
        $reader = new AnnotationReader();
        $reflector = new ReflectionClass($className);
        
        // Get all methods in the controller
        foreach ($reflector->getMethods() as $method) {
            $annotations = $reader->getMethodAnnotations($method);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Route) {
                    $handler = [$className, $method->getName()];

                    // Parse methods (e.g., "GET,POST") and register each method
                    $methods = array_map('trim', explode(',', trim($annotation->getMethods(), '{}')));

                    foreach($methods as $httpMethod) {
                        $this->router->add(
                            strtoupper($httpMethod),
                            $annotation->getPath(),
                            $handler,
                            $annotation->getName()
                        );
                    }
                }
            }
        }
    }

    /**
     * Get the globally accessible router.
     */
    public function getRouter()
    {
        return $this->router;
    }
}
