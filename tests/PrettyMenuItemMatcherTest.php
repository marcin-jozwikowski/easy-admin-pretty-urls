<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemMatcher;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use MarcinJozwikowski\EasyAdminPrettyUrls\Menu\PrettyMenuItemMatcher;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Menu\PrettyMenuItemMatcher
 */
class PrettyMenuItemMatcherTest extends TestCase
{
    private MenuItemMatcher|MockObject $menuItemMatcher;
    private PrettyUrlsResolver|MockObject $prettyUrlsResolver;
    private PrettyMenuItemMatcher $tested;
    private Request|MockObject $request;

    public function setUp(): void
    {
        $this->menuItemMatcher = $this->createMock(MenuItemMatcher::class);
        $this->prettyUrlsResolver = $this->createMock(PrettyUrlsResolver::class);

        $requestStack = new RequestStack();
        $adminContextProvider = new AdminContextProvider($requestStack);
        $this->request = $this->createMock(Request::class);
        $this->request->method('get')
            ->with(EA::CONTEXT_REQUEST_ATTRIBUTE)
            ->willReturn(new AdminContext(
                request: $this->request,
                user: null,
                i18nDto: new I18nDto('', '', '', ['']),
                crudControllers: new CrudControllerRegistry([], [], [], []),
                dashboardDto: new DashboardDto(),
                dashboardController: $this->createMock(DashboardControllerInterface::class),
                assetDto: new AssetsDto(),
                crudDto: null,
                entityDto: null,
                searchDto: null,
                menuFactory: new MenuFactory(
                    $adminContextProvider,
                    $this->createMock(AuthorizationCheckerInterface::class),
                    $this->createMock(LogoutUrlGenerator::class),
                    $this->createMock(AdminUrlGeneratorInterface::class),
                    $this->createMock(MenuItemMatcherInterface::class),
                ),
                templateRegistry: TemplateRegistry::new(),
            ));
        $requestStack->push($this->request);

        $this->tested = new PrettyMenuItemMatcher(
            menuItemMatcher: $this->menuItemMatcher,
            prettyUrlsResolver: $this->prettyUrlsResolver,
            adminContextProvider: $adminContextProvider,
        );
    }

    /**
     * @dataProvider isSelectedResolvesProvider
     */
    public function testIsSelectedMultipleCalls(array $menuResolve, array $requestResolve, bool $expectedResult): void
    {
        $url = base64_encode(random_bytes(random_int(4, 8)));
        $url2 = base64_encode(random_bytes(random_int(4, 8)));
        $url3 = base64_encode(random_bytes(random_int(4, 8)));

        $menuItem = new MenuItemDto();
        $menuItem->setLinkUrl($url);
        $menuItem->setType(MenuItemDto::TYPE_CRUD);

        $menuItem2 = new MenuItemDto();
        $menuItem2->setLinkUrl($url3);
        $menuItem2->setType(MenuItemDto::TYPE_CRUD);

        $this->prettyUrlsResolver->expects(self::exactly(3))
            ->method('resolveToParams')
            ->withConsecutive([$url2], [$url], [$url3])
            ->willReturnOnConsecutiveCalls(
                $requestResolve,
                $menuResolve,
                $menuResolve,
            );
        $this->request->expects(self::exactly(2))
            ->method('getUri')
            ->willReturn($url2);

        self::assertSame($expectedResult, $this->tested->isSelected($menuItem));
        self::assertSame($expectedResult, $this->tested->isSelected($menuItem2));
    }

    public function testSelectedForMenuSection(): void
    {
        $menuItem = new MenuItemDto();
        $menuItem->setType(MenuItemDto::TYPE_SECTION);

        self::assertFalse($this->tested->isSelected($menuItem));
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testSelectedWithNotSolvableUrl(bool $expectedResult): void
    {
        $url = base64_encode(random_bytes(random_int(4, 8)));
        $url2 = base64_encode(random_bytes(random_int(4, 8)));
        $menuItem = new MenuItemDto();
        $menuItem->setLinkUrl($url);
        $menuItem->setType(MenuItemDto::TYPE_CRUD);

        $this->request->expects(self::exactly(2))
            ->method('getUri')
            ->willReturn($url2);

        $this->prettyUrlsResolver->expects(self::exactly(2))
            ->method('resolveToParams')
            ->withConsecutive([$url2], [$url])
            ->willReturnOnConsecutiveCalls(
                [[]],
                $this->throwException(new ResourceNotFoundException()),
            );

        $this->menuItemMatcher->expects(self::once())
            ->method('isSelected')
            ->with($menuItem)
            ->willReturn($expectedResult);

        self::assertSame($expectedResult, $this->tested->isSelected($menuItem));
    }

    /**
     * @dataProvider UrlMenuProvider
     */
    public function testIsSelectedWithUrlMenuItem(string $linkUrl, string $requestUri, bool $expectedResult)
    {
        $menuItem = new MenuItemDto();
        $menuItem->setLinkUrl($linkUrl);
        $menuItem->setType(MenuItemDto::TYPE_URL);

        $this->prettyUrlsResolver->expects(self::once())
            ->method('resolveToPath')
            ->with($requestUri)
            ->willReturn($requestUri);

        $this->request->expects(self::exactly(2))
            ->method('getUri')
            ->willReturn($requestUri);

        $this->request->expects(self::once())
            ->method('getSchemeAndHttpHost')
            ->willReturn('http://domain');

        self::assertSame($expectedResult, $this->tested->isSelected($menuItem));
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testIsExpanded(bool $expectedResult): void
    {
        $menuItem = new MenuItemDto();
        $menuItem->setType(MenuItemDto::TYPE_CRUD);
        $this->menuItemMatcher->expects(self::once())
            ->method('isExpanded')
            ->with($menuItem)
            ->willReturn($expectedResult);

        $actualResult = $this->tested->isExpanded($menuItem);

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @dataProvider isSelectedResolvesProvider
     */
    public function testMarkSelectedMenuItem(array $menuItemResolve, array $requestResolve, bool $expectedResult): void
    {
        $url = base64_encode(random_bytes(random_int(4, 8)));
        $url2 = base64_encode(random_bytes(random_int(4, 8)));
        $url3 = base64_encode(random_bytes(random_int(4, 8)));

        $menuItem = new MenuItemDto();
        $menuItem->setType(MenuItemDto::TYPE_CRUD);
        $menuItem->setLinkUrl($url);

        $menuItem2 = new MenuItemDto();
        $menuItem2->setLinkUrl($url3);
        $menuItem2->setType(MenuItemDto::TYPE_CRUD);
        $menuItem->setSubItems([$menuItem2]);
        $menuItem3 = new MenuItemDto();
        $menuItem3->setType(MenuItemDto::TYPE_SECTION);

        $this->prettyUrlsResolver
            ->expects(self::exactly(3))
            ->method('resolveToParams')
            ->withConsecutive([$url2], [$url3], [$url])
            ->willReturnOnConsecutiveCalls(
                $requestResolve,
                $menuItemResolve,
                $menuItemResolve,
            );
        $this->request->expects(self::exactly(2))
            ->method('getUri')
            ->willReturn($url2);

        $results = $this->tested->markSelectedMenuItem([$menuItem, $menuItem3], $this->request);

        self::assertCount(2, $results);
        self::assertSame($expectedResult, $results[0]->isSelected());
        self::assertSame($expectedResult, $results[0]->isExpanded());
        self::assertFalse($results[1]->isSelected());
        self::assertFalse($results[1]->isExpanded());
    }

    public function booleanProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public function isSelectedResolvesProvider(): array
    {
        $routeParam = 'p'.random_int(1, 2000);
        $routeValue = 'v'.random_int(1, 2000);

        return [
            'one value matching' => [
                [$routeParam => $routeValue],
                [$routeParam => $routeValue],
                true,
            ],
            'one value not matching' => [
                [$routeParam => $routeValue],
                [$routeParam => 'prefix'.$routeValue],
                false,
            ],
            'multi keys non-index matching' => [
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                true,
            ],
            'multi keys non-index not matching' => [
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => 'pref'.$routeValue,
                ],
                false,
            ],
            'multi keys index matching' => [
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    EA::CRUD_ACTION => Crud::PAGE_INDEX,
                    EA::ENTITY_ID => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    EA::CRUD_ACTION => Crud::PAGE_INDEX,
                    EA::ENTITY_ID => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                true,
            ],
            'multi keys index not matching' => [
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    EA::CRUD_ACTION => Crud::PAGE_INDEX,
                    EA::ENTITY_ID => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => $routeValue,
                ],
                [
                    EA::REFERRER => base64_encode(random_bytes(random_int(4, 8))),
                    EA::PAGE => base64_encode(random_bytes(random_int(4, 8))),
                    EA::FILTERS => base64_encode(random_bytes(random_int(4, 8))),
                    EA::SORT => base64_encode(random_bytes(random_int(4, 8))),
                    EA::CRUD_ACTION => Crud::PAGE_INDEX,
                    EA::ENTITY_ID => base64_encode(random_bytes(random_int(4, 8))),
                    '_route' => base64_encode(random_bytes(random_int(4, 8))),
                    '_controller' => base64_encode(random_bytes(random_int(4, 8))),
                    $routeParam => 'pref'.$routeValue,
                ],
                false,
            ],
        ];
    }

    public function UrlMenuProvider()
    {
        $url = base64_encode(random_bytes(random_int(4, 8)));
        $url2 = base64_encode(random_bytes(random_int(4, 8)));

        return [
            ['/some/path', '/some/path', true],
            ['/some/path?q=1', '/some/path', true],
            ['/some/'.$url, '/some/'.$url, true],
            ['/some/path?q='.$url, '/some/path', true],
            ['http://domain/some/path', '/some/path', true],
            ['http://domain/some/'.$url, '/some/'.$url, true],
            ['/some/path/further', '/some/path', false],
            ['/some/path', '/some/path/further', false],
            ['/some/path/'.$url, '/some/path/1'.$url, false],
        ];
    }
}
