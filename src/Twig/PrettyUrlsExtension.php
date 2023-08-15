<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Twig;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PrettyUrlsExtension extends AbstractExtension
{
    public function __construct(
        protected PrettyUrlsGenerator $prettyUrlsGenerator
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pretty_urls_remove_actions', [$this, 'prettyUrlsRemoveActions'], ['is_safe' => ['html']]),
        ];
    }

    public function prettyUrlsRemoveActions($value): string
    {
        $mainUrl = parse_url($value); // split the main url into part
        $mainQuery = [];
        parse_str($mainUrl['query'], $mainQuery); // parse the query part into key-value array
        $mainQueryReferrerUrl = parse_url($mainQuery['referrer']); // 'referrer' query param is a URL that need sanitation

        $referrerParams = [];
        parse_str(urldecode($mainQueryReferrerUrl['query']), $referrerParams);
        $prettyReferrerParams = $this->prettyUrlsGenerator->cleanUpParametersArray($referrerParams);
        unset($prettyReferrerParams[PrettyUrlsGenerator::MENU_PATH]);

        $mainQueryReferrerUrl['query'] = http_build_query($prettyReferrerParams);
        $finalReferrer = '';
        if ($mainQueryReferrerUrl['query']) {
            $finalReferrer .= '?'.$mainQueryReferrerUrl['query'];
        }

        return str_replace('?'.urlencode(http_build_query($referrerParams)), $finalReferrer, $value);
    }
}
