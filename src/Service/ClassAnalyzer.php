<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use MarcinJozwikowski\EasyAdminPrettyUrls\Attribute\PrettyRoutesAction;
use MarcinJozwikowski\EasyAdminPrettyUrls\Attribute\PrettyRoutesController;
use MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedActionAttributeException;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedControllerAttributeException;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

/*
 * This class analyzes reflections of controller classes and extracts route information in form of routeDTOs
 */
class ClassAnalyzer
{
    public function __construct(
        private RouteNamingGenerator $routeNamingGenerator,
        private string $prettyUrlsDefaultDashboard,
        private bool $prettyUrlsIncludeMenuIndex,
    ) {
    }

    /**
     * @return ActionRouteDto[]
     */
    public function getRouteDtosForReflectionClass(ReflectionClass $reflection): array
    {
        $results = [];
        $actions = $this->getActions($reflection);
        foreach ($actions as $action) {
            $actionRoute = $this->getRouteForAction($reflection, $action);
            if ($actionRoute !== null) {
                $results[] = $actionRoute;
            }
        }

        return $results;
    }

    /**
     * @return string[]
     */
    private function getActions(ReflectionClass $reflection): array
    {
        $defaultActions = [
            Action::INDEX,
            Action::NEW,
            Action::DETAIL,
            Action::EDIT,
            Action::DELETE,
            'renderFilters',
        ];

        $attribute = $this->getControllerAttribute($reflection); // get the PrettyRoutesController attribute values
        if ($attribute === null) {
            return $defaultActions; // if none defined - return default actions
        }

        return $attribute->getArguments()[PrettyRoutesController::ARGUMENT_ACTIONS] ?? $defaultActions; // return defined actions or defaults
    }

    private function getRouteForAction(ReflectionClass $reflection, string $action): ?ActionRouteDto
    {
        try {
            $actionAttribute = $this->getActionAttribute($reflection, $action);
        } catch (ReflectionException) {
            return null;
        }

        $actionPath = $action;
        if ($actionAttribute instanceof ReflectionAttribute) {
            // second part of the final URL is the action name or the value in PrettyRoutesAction attribute
            $actionPath = $actionAttribute->getArguments()[PrettyRoutesAction::ARGUMENT_PATH] ?? $action;
        }

        return $this->makeRouteDto(reflection: $reflection, action: $action, actionPath: $actionPath);
    }

    private function getControllerAttribute(ReflectionClass $reflection): ?ReflectionAttribute
    {
        $controllerAttributes = $reflection->getAttributes(PrettyRoutesController::class);
        if (count($controllerAttributes) > 1) {
            throw new RepeatedControllerAttributeException($reflection->getName());
        }
        if (empty($controllerAttributes)) {
            return null;
        }

        return reset($controllerAttributes);
    }

    private function makeRouteDto(ReflectionClass $reflection, string $action, string $actionPath): ActionRouteDto
    {
        $routePathFormat = '/%s/%s';
        $routeDefaults = [
            // set route values
            '_controller' => $this->prettyUrlsDefaultDashboard,
            PrettyUrlsGenerator::EA_FQCN => $reflection->getName(),
            PrettyUrlsGenerator::EA_ACTION => $action,
        ];

        if ($this->prettyUrlsIncludeMenuIndex) {
            // add menu properties when required
            $routePathFormat .= '/{menuPath}';
            $routeDefaults[PrettyUrlsGenerator::MENU_PATH] = '-1,-1';
        }

        $simpleName = $this->routeNamingGenerator->generateSimplifiedClassName($reflection->getName());
        // determine the first part of the final URL - classname or the value from PrettyRoutesController attribute
        $classAttribute = $this->getControllerAttribute($reflection);
        $classPath = $classAttribute?->getArguments()[PrettyRoutesController::ARGUMENT_PATH] ?? $simpleName;

        return new ActionRouteDto(
            name: $this->routeNamingGenerator->generateRouteName($simpleName, $action),
            path: sprintf($routePathFormat, $classPath, $actionPath),
            defaults: $routeDefaults,
        );
    }

    private function getActionAttribute(ReflectionClass $reflection, string $action): ?ReflectionAttribute
    {
        $reflectionMethod = $reflection->getMethod($action); // just assume the required action exists
        $actionAttributes = $reflectionMethod->getAttributes(PrettyRoutesAction::class);
        if (count($actionAttributes) > 1) {
            throw new RepeatedActionAttributeException($reflection->getName(), $action);
        }

        $singleAttribute = reset($actionAttributes); // there should be only one attribute
        if ($singleAttribute instanceof ReflectionAttribute) {
            return $singleAttribute;
        }

        return null;
    }
}
