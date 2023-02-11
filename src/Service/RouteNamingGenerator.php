<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Service;

class RouteNamingGenerator
{
    public function __construct(
        private string $prettyUrlsRoutePrefix,
    ) {
    }

    public function generateSimplifiedClassName(string $fqcn): string
    {
        $classNameA = explode('\\', $fqcn);
        $className = end($classNameA);
        $className = str_replace(['Controller', 'Crud'], ['', ''], $className);

        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($className)));
    }

    public function generateRouteName(string $className, string $actionName): string
    {
        return sprintf('%s_%s_%s', $this->prettyUrlsRoutePrefix, $className, strtolower($actionName));
    }
}
