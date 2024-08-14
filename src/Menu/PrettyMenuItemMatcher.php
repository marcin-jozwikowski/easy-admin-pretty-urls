<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemMatcher;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use function in_array;

use const ARRAY_FILTER_USE_KEY;

/**
 * This class implements two versions of MenuItemMatcherInterface:
 * >= 4.8.1 & <4.11.0 with isSelected() and isExpanded()
 * >=4.11.0 with markSelectedMenuItem
 *
 * Both implementations utilize the same logic - resolve the pretty URL back to parameters,
 * and compare current request to menuItem
 */
class PrettyMenuItemMatcher implements MenuItemMatcherInterface
{
    private const NEVER_SELECTED_MENU_TYPES = [
        MenuItemDto::TYPE_SECTION,
        MenuItemDto::TYPE_SUBMENU,
        MenuItemDto::TYPE_EXIT_IMPERSONATION,
        MenuItemDto::TYPE_LOGOUT,
    ];

    private array $requestParameters;
    private string $requestPath;
    private string $requestShemeAndHost;

    public function __construct(
        private MenuItemMatcher $menuItemMatcher,
        private PrettyUrlsResolver $prettyUrlsResolver,
        private AdminContextProvider $adminContextProvider,
    ) {
    }

    /**
     * Verifies if the menuItem provided should be marked as selected
     * The function extracts the EA query params back from the route
     * and compares them with current request (after some sanitation).
     */
    public function isSelected(MenuItemDto $menuItemDto): bool
    {
        $adminContext = $this->adminContextProvider->getContext();
        if (null === $adminContext || in_array($menuItemDto->getType(), self::NEVER_SELECTED_MENU_TYPES)) {
            return false;
        }

        // ensure current request parameters are loaded
        $this->setUpRequestParameters($adminContext);

        if ($menuItemDto->getType() === MenuItemDto::TYPE_URL) {
            $menuUrl = strtok($menuItemDto->getLinkUrl(), '?'); // drop the query params from URL
            if (str_starts_with($menuUrl, '/')) {
                // if the link is a relative one - compare it against path
                return $menuUrl === $this->requestPath;
            }

            // absolute path - compare it against schema, domain, and path
            return $menuUrl === $this->requestShemeAndHost.$this->requestPath;
        }

        try {
            // get a matching route based on menuItem link URI
            $menuRouteParamsRaw = $this->prettyUrlsResolver->resolveToParams($menuItemDto->getLinkUrl());
        } catch (ResourceNotFoundException) {
            // error fetching route - just pass through to EA matcher
            return $this->menuItemMatcher->isSelected($menuItemDto);
        }

        // remove parameters not used in comparison
        $menuItemLinksToIndexCrudAction = Crud::PAGE_INDEX === ($menuRouteParamsRaw[EA::CRUD_ACTION] ?? false);
        $menuRouteParams = $this->filterIrrelevantQueryParameters($menuRouteParamsRaw, $menuItemLinksToIndexCrudAction);
        $requestParameters = $this->filterIrrelevantQueryParameters($this->requestParameters, $menuItemLinksToIndexCrudAction);

        return $requestParameters === $menuRouteParams;
    }

    /**
     * Just re-using the existing logic - if any child is selected the whole branch is expanded.
     */
    public function isExpanded(MenuItemDto $menuItemDto): bool
    {
        return $this->menuItemMatcher->isExpanded($menuItemDto);
    }

    /**
     * @param MenuItemDto[] $menuItems
     *
     * @return MenuItemDto[]
     */
    public function markSelectedMenuItem(array $menuItems, Request $request): array
    {
        $this->doMarkSelectedMenuItem($menuItems, $request);
        $this->doMarkExpandedMenuItem($menuItems);

        return $menuItems;
    }

    /**
     * Parses the current request from AdminContext into a series.
     */
    private function setUpRequestParameters(AdminContext $adminContext): void
    {
        if (isset($this->requestParameters)) {
            // already been parsed - no need to do it again
            return;
        }

        $this->requestParameters = $this->prettyUrlsResolver->resolveToParams($adminContext->getRequest()->getUri());
        $this->requestPath = $this->prettyUrlsResolver->resolveToPath($adminContext->getRequest()->getUri());
        $this->requestShemeAndHost = $adminContext->getRequest()->getSchemeAndHttpHost();
    }

    private function filterIrrelevantQueryParameters(array $queryStringParameters, bool $menuItemLinksToIndexCrudAction): array
    {
        $paramsToRemove = [EA::REFERRER, EA::PAGE, EA::FILTERS, EA::SORT, '_route', '_controller'];

        if ($menuItemLinksToIndexCrudAction) {
            $paramsToRemove[] = EA::CRUD_ACTION;
            $paramsToRemove[] = EA::ENTITY_ID;
        }

        $result = array_filter($queryStringParameters, static fn ($k) => !in_array($k, $paramsToRemove, true), ARRAY_FILTER_USE_KEY);
        sort($result);

        return $result;
    }

    /**
     * @param MenuItemDto[] $menuItems
     *
     * @return MenuItemDto[]
     */
    private function doMarkSelectedMenuItem(array $menuItems, Request $request): array
    {
        foreach ($menuItems as $menuItemDto) {
            if ([] !== $subItems = $menuItemDto->getSubItems()) {
                $menuItemDto->setSubItems($this->doMarkSelectedMenuItem($subItems, $request));
            }

            $menuItemDto->setSelected($this->isSelected($menuItemDto));
        }

        return $menuItems;
    }

    /**
     * @param MenuItemDto[] $menuItems
     *
     * @return MenuItemDto[]
     */
    private function doMarkExpandedMenuItem(array $menuItems): array
    {
        foreach ($menuItems as $menuItemDto) {
            if ([] === $menuSubitems = $menuItemDto->getSubItems()) {
                continue;
            }

            foreach ($menuSubitems as $submenuItem) {
                if ($submenuItem->isSelected()) {
                    $menuItemDto->setExpanded(true);

                    break;
                }
            }
        }

        return $menuItems;
    }
}
