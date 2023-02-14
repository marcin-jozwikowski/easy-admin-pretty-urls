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
    public const DEFAULT_DASHBOARD = 'App//Dasboard::index';

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

        self::assertCount(5, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals($this->randomPrefix.'_specific_index', $routes[0]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals($this->randomPrefix.'_specific_new', $routes[1]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals($this->randomPrefix.'_specific_detail', $routes[2]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals($this->randomPrefix.'_specific_edit', $routes[3]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals($this->randomPrefix.'_specific_delete', $routes[4]->getName());
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
        self::assertEquals($this->randomPrefix.'_specific_someaction', $routes[0]->getName());
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
        );

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals($this->randomPrefix.'_specific_someaction', $routes[0]->getName());
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
        self::assertCount(5, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('/specific/newPath', $routes[0]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals('/specific/newPath', $routes[1]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals('/specific/newPath', $routes[2]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals('/specific/newPath', $routes[3]->getPath());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals('/specific/newPath', $routes[4]->getPath());
    }
}
