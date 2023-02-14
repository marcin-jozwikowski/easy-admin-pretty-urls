<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use ExampleClassWithNoNamespace;
use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto;
use MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RouteAlreadyExists;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassAnalyzer;
use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassFinder;
use MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data\ExampleClass;
use MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data\ExampleClassImplementingDashboard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Exception\RouteAlreadyExists
 */
class PrettyRoutesLoaderTest extends TestCase
{
    private MockObject|ClassFinder $classFinder;
    private MockObject|ClassAnalyzer $classAnalyzer;
    private PrettyRoutesLoader $testedClass;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->classFinder = $this->createMock(ClassFinder::class);
        $this->classAnalyzer = $this->createMock(ClassAnalyzer::class);
        $env = base64_encode(random_bytes(random_int(8, 16)));

        $this->testedClass = new PrettyRoutesLoader(
            classFinder: $this->classFinder,
            classAnalyzer: $this->classAnalyzer,
            env: $env,
        );
    }

    /**
     * @throws Exception
     *
     * @dataProvider supportedTypesDataProvider
     */
    public function testSupportedTypes(string $type, bool $expected): void
    {
        $resource = base64_encode(random_bytes(random_int(8, 16)));
        $result = $this->testedClass->supports($resource, $type);

        self::assertEquals($expected, $result);
    }

    /**
     * @return array<int, array<int, string|bool>>
     *
     * @throws Exception
     */
    public function supportedTypesDataProvider(): array
    {
        return [
            [base64_encode(random_bytes(random_int(32, 40))), false],
            ['pretty_routes'.base64_encode(random_bytes(random_int(8, 16))), false],
            [base64_encode(random_bytes(random_int(8, 16))).'pretty_routes', false],
            ['pretty_routes'.base64_encode(random_bytes(random_int(8, 16))).'pretty_routes', false],
            ['pretty_routes', true],
        ];
    }

    /**
     * @throws Exception
     */
    public function testLoad(): void
    {
        $resource = md5(random_bytes(random_int(6, 8)));
        $routeName = md5(random_bytes(random_int(6, 8)));
        $this->classFinder->expects(self::once())
            ->method('getClassNames')
            ->with($resource)
            ->willReturn([
                ExampleClass::class,
            ]);
        $this->classAnalyzer->expects(self::once())
            ->method('getRouteDtosForReflectionClass')
            ->withAnyParameters()
            ->willReturn(
                [
                    new ActionRouteDto(
                        name: $routeName,
                        path: 'path/to/'.$routeName,
                        defaults: [],
                    ),
                ],
            );

        $loadedRoutes = $this->testedClass->load($resource);

        self::assertInstanceOf(RouteCollection::class, $loadedRoutes);
        self::assertCount(1, $loadedRoutes);
        self::assertInstanceOf(Route::class, $loadedRoutes->get($routeName));
    }

    /**
     * @throws Exception
     */
    public function testLoadNoValidClasses(): void
    {
        $resource = md5(random_bytes(random_int(6, 8)));
        $this->classFinder->expects(self::once())
            ->method('getClassNames')
            ->with($resource)
            ->willReturn([
                ExampleClassWithNoNamespace::class,
                ExampleClassImplementingDashboard::class,
                md5(random_bytes(random_int(6, 8))),
            ]);
        $this->classAnalyzer->expects(self::never())
            ->method('getRouteDtosForReflectionClass');

        $loadedRoutes = $this->testedClass->load($resource);

        self::assertInstanceOf(RouteCollection::class, $loadedRoutes);
        self::assertCount(0, $loadedRoutes);
    }

    /**
     * @throws Exception
     */
    public function testLoadDuplicate(): void
    {
        self::expectException(RouteAlreadyExists::class);

        $resource = md5(random_bytes(random_int(6, 8)));
        $routeName = md5(random_bytes(random_int(6, 8)));
        $this->classFinder->expects(self::once())
            ->method('getClassNames')
            ->with($resource)
            ->willReturn([
                ExampleClass::class,
            ]);
        $this->classAnalyzer->expects(self::once())
            ->method('getRouteDtosForReflectionClass')
            ->withAnyParameters()
            ->willReturn(
                [
                    new ActionRouteDto(
                        name: $routeName,
                        path: 'path/to/'.$routeName,
                        defaults: [],
                    ),
                    new ActionRouteDto(
                        name: $routeName,
                        path: 'path/to/'.$routeName,
                        defaults: [],
                    ),
                ],
            );

        $this->testedClass->load($resource);
    }

    /**
     * @throws Exception
     */
    public function testLoadDuplicateDifferentClasses(): void
    {
        self::expectException(RouteAlreadyExists::class);

        $resource = md5(random_bytes(random_int(6, 8)));
        $routeName = md5(random_bytes(random_int(6, 8)));
        $this->classFinder->expects(self::once())
            ->method('getClassNames')
            ->with($resource)
            ->willReturn([
                ExampleClass::class,
                ExampleClass::class,
            ]);
        $this->classAnalyzer->expects(self::atMost(2))
            ->method('getRouteDtosForReflectionClass')
            ->withAnyParameters()
            ->willReturn(
                [
                    new ActionRouteDto(
                        name: $routeName,
                        path: 'path/to/'.$routeName,
                        defaults: [],
                    ),
                ],
            );

        $loadedRoutes = $this->testedClass->load($resource);
    }
}
