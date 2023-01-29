<?php

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
use Symfony\Component\Routing\Route;

class ClassAnalyzer
{
    public function __construct(
        private string $prettyUrlsDefaultDashboard,
        private string $prettyUrlsRoutePrefix,
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
        ];

        $attribute = $this->getControllerAttribute($reflection);
        if ($attribute === null) {
            return $defaultActions;
        }

        return $attribute->getArguments()[PrettyRoutesController::ARGUMENT_ACTIONS] ?? $defaultActions;
    }

    private function getRouteForAction(ReflectionClass $reflection, string $action): ?ActionRouteDto
    {
        try {
            $reflectionMethod = $reflection->getMethod($action);
        } catch (ReflectionException) {
            return null;
        }

        $actionAttribute = $reflectionMethod->getAttributes(PrettyRoutesAction::class);
        if (count($actionAttribute) > 1) {
            throw new RepeatedActionAttributeException($reflection->getName(), $action);
        }

        return $this->makeRouteDto($reflection, $action);
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

    private function getSimplifiedControllerName(ReflectionClass $reflection): string
    {
        // @todo Make a common function for this and PrettyUrlGenerator - those are exactly the same
        $classNameA = explode('\\', $reflection->getName());
        $className = end($classNameA);
        $className = str_replace(['Controller', 'Crud'], ['', ''], $className);

        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($className)));
    }

    private function makeRouteDto(ReflectionClass $reflection, string $action): ActionRouteDto
    {
        $routePathFormat = '/%s/%s';
        $routeDefaults = [
            '_controller' => $this->prettyUrlsDefaultDashboard,
            PrettyUrlsGenerator::EA_FQCN => $reflection->getName(),
            PrettyUrlsGenerator::EA_ACTION => $action,
        ];

        if ($this->prettyUrlsIncludeMenuIndex) {
            $routePathFormat .= '/DEFAULT_DASHBOARD_NODE{menuPath}';
            $routeDefaults[PrettyUrlsGenerator::MENU_PATH] = '-1,-1';
        }

        $simpleName = $this->getSimplifiedControllerName($reflection);
        $oneRoute = new Route(
            path: sprintf($routePathFormat, $simpleName, $action),
        ); // @todo Utilize PrettyAttribute in both path parts
        $oneRoute->setDefaults($routeDefaults);

        return new ActionRouteDto(
            // @todo Make a common function for :name and PrettyUrlGenerator - those are exactly the same
            name: sprintf('%s_%s_%s', $this->prettyUrlsRoutePrefix, $simpleName, $action),
            route: $oneRoute,
        );
    }
}
