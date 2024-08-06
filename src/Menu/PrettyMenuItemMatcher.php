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
    private array $requestParameters;

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
        if (null === $adminContext || $menuItemDto->isMenuSection()) {
            return false;
        }

        try {
            // get a matching route based on menuItem link URI
            $menuRouteParams = $this->prettyUrlsResolver->resolveToParams($menuItemDto->getLinkUrl());
        } catch (ResourceNotFoundException) {
            // error fetching route - just pass through to EA matcher
            return $this->menuItemMatcher->isSelected($menuItemDto);
        }

        // ensure current request parameters are loaded
        $this->setUpRequestParameters($adminContext);

        // remove parameters not used in comparison
        $menuItemLinksToIndexCrudAction = Crud::PAGE_INDEX === ($menuRouteParams[EA::CRUD_ACTION] ?? false);
        $menuRouteParams = $this->filterIrrelevantQueryParameters($menuRouteParams, $menuItemLinksToIndexCrudAction);
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
