<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Routing;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassFinder;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class PrettyRoutesLoader extends Loader
{
    public function __construct(
        private ClassFinder $classFinder,
        private ClassAnalyzer $classAnalyzer,
        string $env = null,
    ) {
        parent::__construct($env);
    }

    public function load($resource, string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $classes = $this->classFinder->getClassNames($resource);
        foreach ($classes as $singleClass) {
            $routes->addCollection($this->getRoutesForClass($singleClass));
        }

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'pretty_routes' === $type;
    }

    private function getRoutesForClass(string $singleClass): RouteCollection
    {
        $routes = new RouteCollection();

        try {
            $reflection = new ReflectionClass($singleClass);
        } catch (ReflectionException) {
            return $routes;
        }

        if ($reflection->implementsInterface(DashboardControllerInterface::class)) {
            return $routes;
        }

        $routeDtos = $this->classAnalyzer->getRouteDtosForReflectionClass($reflection);
        foreach ($routeDtos as $routeDto) {
            // @todo Check if the route has not been added before
            $routes->add($routeDto->getName(), $routeDto->getRoute());
        }

        return $routes;
    }
}
