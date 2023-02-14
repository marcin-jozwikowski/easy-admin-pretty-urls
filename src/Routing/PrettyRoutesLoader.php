<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Routing;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RouteAlreadyExists;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassFinder;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
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

    /**
     * @throws RouteAlreadyExists
     */
    public function load($resource, string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $classes = $this->classFinder->getClassNames($resource);
        foreach ($classes as $singleClass) {
            $classRoutes = $this->getRoutesForClass($singleClass);
            foreach ($classRoutes as $singleRouteName => $singleRoute) {
                if ($routes->get($singleRouteName)) {
                    throw new RouteAlreadyExists($singleRouteName);
                }
            }
            $routes->addCollection($classRoutes);
        }

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'pretty_routes' === $type;
    }

    /**
     * @throws RouteAlreadyExists
     */
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
            if ($routes->get($routeDto->getName())) {
                throw new RouteAlreadyExists($routeDto->getName());
            }
            $routes->add(
                name: $routeDto->getName(),
                route: new Route(
                    path: $routeDto->getPath(),
                    defaults: $routeDto->getDefaults(),
                ),
            );
        }

        return $routes;
    }
}
