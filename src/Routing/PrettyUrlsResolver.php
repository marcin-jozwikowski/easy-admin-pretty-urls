<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Routing;

use Symfony\Component\Routing\RouterInterface;

class PrettyUrlsResolver
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function resolveToParams(string $path): array
    {
        // defaults from the route
        $requestParams = $this->router->match($this->resolveToPath($path));

        // additional values from the query string
        $requestQueryParams = [];
        $requestQuery = parse_url($path, PHP_URL_QUERY);
        if ($requestQuery) {
            parse_str(urldecode($requestQuery), $requestQueryParams);
        }

        return array_merge($requestParams, $requestQueryParams);
    }

    public function resolveToPath(string $path): string
    {
        return parse_url($path, PHP_URL_PATH);
    }
}
