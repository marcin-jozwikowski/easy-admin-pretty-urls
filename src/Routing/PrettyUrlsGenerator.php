<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Routing;

use MarcinJozwikowski\EasyAdminPrettyUrls\Service\RouteNamingGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/*
 * This class utilizes the routes created in Loader class instead of just creating URL with all data in query
 */
class PrettyUrlsGenerator implements UrlGeneratorInterface
{
    public const EA_FQCN = 'crudControllerFqcn';
    public const EA_ACTION = 'crudAction';
    public const EA_MENU_INDEX = 'menuIndex';
    public const EA_SUBMENU_INDEX = 'submenuIndex';
    public const MENU_PATH = 'menuPath';

    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
        private RouteNamingGenerator $routeNamingGenerator,
        private bool $prettyUrlsIncludeMenuIndex,
    ) {
    }

    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        // at first check if all necessary params are provided
        if (isset($parameters[static::EA_FQCN]) && isset($parameters[static::EA_ACTION])) {
            $prettyName = $this->generateNameFromParameters($parameters); // get name of the route to use
            $prettyParams = $parameters; // copy all parameters and remove those that are defined in the route
            unset($prettyParams[static::EA_FQCN]);
            unset($prettyParams[static::EA_ACTION]);

            if ($this->prettyUrlsIncludeMenuIndex && $menuIndex = $this->generateMenuIndexPart($parameters)) {
                // when the route can contain menu information - remove that from the parameters
                unset($prettyParams[static::EA_MENU_INDEX]);
                unset($prettyParams[static::EA_SUBMENU_INDEX]);
                $prettyParams[self::MENU_PATH] = $menuIndex;
            }

            try {
                // generate the url using the route and any remaining parameters
                return $this->router->generate($prettyName, $prettyParams, $referenceType);
            } catch (RouteNotFoundException $e) {
                $this->logger->debug('Pretty route not found', [
                    'route_name' => $prettyName,
                    static::EA_FQCN => $parameters[static::EA_FQCN],
                    static::EA_ACTION => $parameters[static::EA_ACTION],
                ]);
            }
        }

        // fallback to default behavior for all other URLs
        return $this->router->generate($name, $parameters, $referenceType);
    }

    private function generateNameFromParameters(array $parameters): string
    {
        // get only the classname itself
        $className = $this->routeNamingGenerator->generateSimplifiedClassName($parameters[static::EA_FQCN]);

        // route name consists of classname and action
        return $this->routeNamingGenerator->generateRouteName($className, $parameters[static::EA_ACTION]);
    }

    private function generateMenuIndexPart(array $parameters): ?string
    {
        return sprintf(
            '%d,%d',
            $parameters[self::EA_MENU_INDEX] ?? -1,
            $parameters[self::EA_SUBMENU_INDEX] ?? -1,
        );
    }
}
