<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Service;

/*
 * This class generates the names for routes
 */
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

        return $this->toSnakeCase($className);
    }

    public function generateRouteName(string $className, string $actionName): string
    {
        return sprintf('%s_%s_%s', $this->prettyUrlsRoutePrefix, $className, $this->toSnakeCase($actionName));
    }

    private function toSnakeCase(string $value): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($value)));
    }
}
