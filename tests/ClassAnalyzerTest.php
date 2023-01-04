<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\Attribute\PrettyRoutesController;
use MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class ClassAnalyzerTest extends TestCase
{
    private MockObject|ReflectionAttribute $reflectionAttribute;
    private ReflectionMethod|MockObject $reflectionMethod;
    private MockObject|ReflectionClass $reflection;
    private ClassAnalyzer $testedAnalyzer;

    public function setUp(): void
    {
        $this->reflectionAttribute = $this->createMock(ReflectionAttribute::class);
        $this->reflectionAttribute->expects(self::any())
            ->method('getArguments')
            ->willReturn([PrettyRoutesController::ARGUMENT_ACTIONS => ['someAction']]);

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

        $this->testedAnalyzer = new ClassAnalyzer();
    }

    public function testDefaultBehaviour(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(5, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('pretty_specific_index', $routes[0]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[1]);
        self::assertEquals('pretty_specific_new', $routes[1]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[2]);
        self::assertEquals('pretty_specific_detail', $routes[2]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[3]);
        self::assertEquals('pretty_specific_edit', $routes[3]->getName());
        self::assertInstanceOf(ActionRouteDto::class, $routes[4]);
        self::assertEquals('pretty_specific_delete', $routes[4]->getName());
    }

    public function testActionsProvidedInAttribute(): void
    {
        $this->reflection->expects(self::any())
            ->method('getAttributes')
            ->with(PrettyRoutesController::class)
            ->willReturn([$this->reflectionAttribute]);

        $routes = $this->testedAnalyzer->getRouteDtosForReflectionClass($this->reflection);

        self::assertCount(1, $routes);
        self::assertInstanceOf(ActionRouteDto::class, $routes[0]);
        self::assertEquals('pretty_specific_someAction', $routes[0]->getName());
    }
}
