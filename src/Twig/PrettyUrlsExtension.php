<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Twig;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PrettyUrlsExtension extends AbstractExtension
{
    public function __construct(
        protected PrettyUrlsGenerator $prettyUrlsGenerator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pretty_urls_remove_actions', [$this, 'prettyUrlsRemoveActions'], ['is_safe' => ['html']]),
        ];
    }

    public function prettyUrlsRemoveActions(string $value): string
    {
        $mainUrlQuery = parse_url($value, PHP_URL_QUERY); // split the main url into part
        if (empty($mainUrlQuery)) {
            return $value;
        }
        $mainQueryParams = [];
        parse_str($mainUrlQuery, $mainQueryParams); // parse the query part into key-value array
        if (empty($mainQueryParams[PrettyUrlsGenerator::EA_REFERRER])) {
            return $value;
        }

        $matches = [];
        preg_match('#referrer=([\d\w/,-?%]+)[&]?#', $value, $matches); // match the original referrer
        if ($matches[1]) {
            $finalReferrer = $this->prettyUrlsGenerator->sanitizeUrl($mainQueryParams[PrettyUrlsGenerator::EA_REFERRER]);

            return str_replace($matches[1], $finalReferrer, $value); // replace the old referrer with the new one
        }

        return $value;
    }
}
