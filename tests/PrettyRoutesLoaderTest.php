<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use ExampleClassWithNoNamespace;
use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Dto\ActionRouteDto;
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
                        route: new Route(path: 'path/to/'.$routeName),
                    ),
                ]
            );

        $loadedRoutes = $this->testedClass->load($resource);

        self::assertInstanceOf(RouteCollection::class, $loadedRoutes);
        self::assertCount(1, $loadedRoutes);
        self::assertInstanceOf(Route::class, $loadedRoutes->get($routeName));
    }

    /**
     * @throws Exception
     */
    public function testLoad_NoValidClasses(): void
    {
        $resource = md5(random_bytes(random_int(6, 8)));
        $routeName = md5(random_bytes(random_int(6, 8)));
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
}
