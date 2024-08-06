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

/*
 * This class creates the routes based on controllers defined in the app.
 * It iterates over all classes in a directory provided in routes.yml
 */
class PrettyRoutesLoader extends Loader
{
    public function __construct(
        private ClassFinder $classFinder,
        private ClassAnalyzer $classAnalyzer,
        ?string $env = null,
    ) {
        parent::__construct($env);
    }

    /**
     * @throws RouteAlreadyExists
     */
    public function load($resource, ?string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $classes = $this->classFinder->getClassNames($resource); // resource is a path to controllers dir - get all classes in there
        foreach ($classes as $singleClass) {
            $classRoutes = $this->getRoutesForClass($singleClass); // get all routes for each of those classes
            foreach ($classRoutes as $singleRouteName => $singleRoute) {
                if ($routes->get($singleRouteName)) { // check created routes against existing ones for any duplicates
                    throw new RouteAlreadyExists($singleRouteName);
                }
            }
            $routes->addCollection($classRoutes); // add new classes to the pool
        }

        return $routes;
    }

    public function supports($resource, ?string $type = null): bool
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
            // this is the main controller - cannot map that one
            return $routes;
        }

        // get all routeDTOs based on Attributes in the controller class and/or default behaviors
        $routeDtos = $this->classAnalyzer->getRouteDtosForReflectionClass($reflection);
        foreach ($routeDtos as $routeDto) {
            if ($routes->get($routeDto->getName())) {
                // check for any duplicates
                throw new RouteAlreadyExists($routeDto->getName());
            }

            // generate actual Symfony routes from the DTOs
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
