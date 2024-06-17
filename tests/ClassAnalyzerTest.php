<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Attribute\PrettyRoutesAction;
use MarcinJozwikowski\EasyAdminPrettyUrls\Attribute\PrettyRoutesController;
use MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedActionAttributeException;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedControllerAttributeException;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\RouteNamingGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Service\RouteNamingGenerator
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedActionAttributeException
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RepeatedControllerAttributeException
 */
class ClassAnalyzerTest extends TestCase
{
    private const DEFAULT_DASHBOARD = 'App//Dasboard::index';

    private MockObject|ReflectionAttribute $reflectionAttribute;
    private ReflectionMethod|MockObject $reflectionMethod;
    private MockObject|ReflectionClass $reflection;
    private string $randomPrefix;
    private ClassAnalyzer $testedAnalyzer;
    private MockObject|ReflectionAttribute $reflectionActionAttribute;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_ACTIONS => ['someAction']]);

        $this->reflectionActionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionActionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesAction::ARGUMENT_PATH => 'newPath']);

        $this->reflectionMethod = $this->createMock(ReflectionMethod::class);

        $this->reflection = $this->createMock(ReflectionClass::class);
        $this->reflection->expects(self::any())
            ->method('getName')
            ->withAnyParameters()
            ->willReturn('App\\Namespace\\SpecificCrudController');

        $this->reflection->expects(self::any())
            ->method('getMethod')
            ->withAnyParameters()
            ->willReturn($this->reflectionMethod);

        $this->randomPrefix = base64_encode(random_bytes(random_int(1, 3)));

        $this->testedAnalyzer = new ClassAnalyzer(
            routeNamingGenerator: new RouteNamingGenerator($this->randomPrefix),
            prettyUrlsDefaultDashboard: self::DEFAULT_DASHBOARD,
            prettyUrlsIncludeMenuIndex: false,
            prettyUrlsDefaultActions: PrettyRoutesController::DEFAULT_ACTIONS,
        );
    }

    /*
     * Each action in default list will return the same default parameters
     */
    public function testDefaultBehaviour(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(8, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals($this->randomPrefix.'_specific_crud_index', $routes[0]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals($this->randomPrefix.'_specific_crud_new', $routes[1]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals($this->randomPrefix.'_specific_crud_detail', $routes[2]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals($this->randomPrefix.'_specific_crud_edit', $routes[3]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals($this->randomPrefix.'_specific_crud_delete', $routes[4]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[5]);
        self::assertEquals($this->randomPrefix.'_specific_crud_batch_delete', $routes[5]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[6]);
        self::assertEquals($this->randomPrefix.'_specific_crud_render_filters', $routes[6]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[7]);
        self::assertEquals($this->randomPrefix.'_specific_crud_autocomplete', $routes[7]->getName());
    }

    /*
     * Actions list is defined in attribute
     */
    public function testActionsProvidedInAttribute(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals($this->randomPrefix.'_specific_crud_some_action', $routes[0]->getName());
    }

    /*
     * Actions list is defined in attribute and menu index should be included in route
     */
    public function testMenuIndexEnabledInRoutes(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $this->testedAnalyzer = new ClassAnalyzer(
            routeNamingGenerator: new RouteNamingGenerator($this->randomPrefix),
            prettyUrlsDefaultDashboard: self::DEFAULT_DASHBOARD,
            prettyUrlsIncludeMenuIndex: true,
            prettyUrlsDefaultActions: PrettyRoutesController::DEFAULT_ACTIONS,
        );

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals($this->randomPrefix.'_specific_crud_some_action', $routes[0]->getName());
        self::assertArrayHasKey('_controller', $routes[0]->getDefaults());
        self::assertEquals(self::DEFAULT_DASHBOARD, $routes[0]->getDefaults()['_controller']);
        self::assertArrayHasKey('crudControllerFqcn', $routes[0]->getDefaults());
        self::assertArrayHasKey('crudAction', $routes[0]->getDefaults());
        self::assertArrayHasKey('menuPath', $routes[0]->getDefaults());
    }

    /*
     * Default action list is used but this time there's a ReflectionException for each call
     */
    public function testReflectionExceptionOnGetMethod(): void
    {
        $this->reflection->expects(self::any())
            ->method('getMethod')
            ->withAnyParameters()
            ->willThrowException(new ReflectionException());

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(0, $routes);
    }

    public function testDuplicatedAttributeForAction(): void
    {
        $this->reflectionMethod->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesAction::class)
            ->willReturn([$this->reflectionAttribute, $this->reflectionAttribute]);

        self::expectException(RepeatedActionAttributeException::class);
        self::expectExceptionMessage('More than one PrettyRoutesAction attribute was found in App\Namespace\SpecificCrudController::index');
        $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
    }

    public function testDuplicatedAttributeForController(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute, $this->reflectionAttribute]);

        self::expectException(RepeatedControllerAttributeException::class);
        self::expectExceptionMessage('More than one PrettyRoutesController attribute was found in App\Namespace\SpecificCrudController');
        $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
    }

    public function testActionAttributePath(): void
    {
        $this->reflectionMethod->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesAction::class)
            ->willReturn([$this->reflectionActionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(8, $routes);
        foreach ($routes as $singleRoute) {
            self::assertInstanceOf(ActionRouteDto::class, $singleRoute);
            self::assertEquals('/specific_crud/newPath', $singleRoute->getPath());
        }
    }

    public function testClassAttributePath(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_PATH => 'differentPath']);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(8, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/differentPath/index', $routes[0]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals('/differentPath/new', $routes[1]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals('/differentPath/detail', $routes[2]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals('/differentPath/edit', $routes[3]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals('/differentPath/delete', $routes[4]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[5]);
        self::assertEquals('/differentPath/batchDelete', $routes[5]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[6]);
        self::assertEquals('/differentPath/renderFilters', $routes[6]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[7]);
        self::assertEquals('/differentPath/autocomplete', $routes[7]->getPath());
    }

    public function testClassAndActionAttributePath(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_PATH => 'differentPath']);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $this->reflectionMethod->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesAction::class)
            ->willReturn([$this->reflectionActionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(8, $routes);
        foreach ($routes as $singleRoute) {
            self::assertInstanceOf(ActionRouteDto::class, $singleRoute);
            self::assertEquals('/differentPath/newPath', $singleRoute->getPath());
        }
    }

    public function testCustomActionsDefined(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_CUSTOM_ACTIONS => ['customAction']]);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(9, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific_crud/index', $routes[0]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals('/specific_crud/new', $routes[1]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals('/specific_crud/detail', $routes[2]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals('/specific_crud/edit', $routes[3]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals('/specific_crud/delete', $routes[4]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[5]);
        self::assertEquals('/specific_crud/batchDelete', $routes[5]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[6]);
        self::assertEquals('/specific_crud/renderFilters', $routes[6]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[7]);
        self::assertEquals('/specific_crud/autocomplete', $routes[7]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[8]);
        self::assertEquals('/specific_crud/customAction', $routes[8]->getPath());
    }

    public function testActionsDefined(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_ACTIONS => ['index']]);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific_crud/index', $routes[0]->getPath());
    }

    public function testCustomActionsAndActionsDefined(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([
                PrettyRoutesController::ARGUMENT_ACTIONS => ['index'],
                PrettyRoutesController::ARGUMENT_CUSTOM_ACTIONS => ['customAction'],
            ]);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);
        self::assertCount(2, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific_crud/index', $routes[0]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals('/specific_crud/customAction', $routes[1]->getPath());
    }

    /**
     * @throws RandomException
     */
    public function testDefaultActionsRedefined(): void
    {
        $actions = [];
        for ($i = random_int(1, 5); $i >= 0; --$i) {
            $actions[] = base64_encode(random_bytes(random_int(10, 16)));
        }
        $actions = array_unique($actions);

        $this->testedAnalyzer = new ClassAnalyzer(
            routeNamingGenerator: new RouteNamingGenerator($this->randomPrefix),
            prettyUrlsDefaultDashboard: self::DEFAULT_DASHBOARD,
            prettyUrlsIncludeMenuIndex: false,
            prettyUrlsDefaultActions: $actions,
        );

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(sizeof($actions), $routes);
        foreach ($routes as $id => $route) {
            self::assertInstanceOf(ActionRouteDto::class, $route);
            self::assertEquals('/specific_crud/'.$actions[$id], $route->getPath());
        }
    }

    public function testDuplicatesFromDefaultsAndCustomsRemoved()
    {
        $action = base64_encode(random_bytes(random_int(10, 16)));

        $this->testedAnalyzer = new ClassAnalyzer(
            routeNamingGenerator: new RouteNamingGenerator($this->randomPrefix),
            prettyUrlsDefaultDashboard: self::DEFAULT_DASHBOARD,
            prettyUrlsIncludeMenuIndex: false,
            prettyUrlsDefaultActions: [$action],
        );

        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_CUSTOM_ACTIONS => [$action]]);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific_crud/'.$action, $routes[0]->getPath());
    }

    public function testControllerAttribute()
    {
        $controller = base64_encode(random_bytes(random_int(10, 16)));

        $this->testedAnalyzer = new ClassAnalyzer(
            routeNamingGenerator: new RouteNamingGenerator($this->randomPrefix),
            prettyUrlsDefaultDashboard: self::DEFAULT_DASHBOARD,
            prettyUrlsIncludeMenuIndex: false,
            prettyUrlsDefaultActions: ['default'],
        );

        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_DASHBOARD => $controller]);

        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific_crud/default', $routes[0]->getPath());
        self::assertEquals($controller, $routes[0]->getDefaults()['_controller']);
    }
}
