<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;

use function PHPUnit\Framework\at;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PrettyUrlsGeneratorTest extends TestCase
{
    private const INITIAL_ROUTE_NAME = 'route_name';
    private RouterInterface|MockObject $router;
    private LoggerInterface|MockObject $logger;
    private PrettyUrlsGenerator $testedClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->testedClass = new PrettyUrlsGenerator(
            router: $this->router,
            logger: $this->logger,
        );
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $expectedParams
     *
     * @dataProvider generateDataProvider
     *
     * @throws \Exception
     */
    public function testGenerate(array $params, string $expectedName, array $expectedParams): void
    {
        $expectedResult = base64_encode(random_bytes(16));
        $this->logger->expects(self::never())
            ->method('debug');
        $this->router->expects(self::once())
            ->method('generate')
            ->with($expectedName, $expectedParams, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expectedResult);

        $result = $this->testedClass->generate(self::INITIAL_ROUTE_NAME, $params);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @throws \Exception
     */
    public function testGenerateNotFound(): void
    {
        $expectedResult = base64_encode(random_bytes(16));
        $params = [
            'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
            'crudAction' => 'index',
        ];

        $this->router->expects(at(0))
            ->method('generate')
            ->with('some_entity_index', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willThrowException(new RouteNotFoundException());

        $this->router->expects(at(1))
            ->method('generate')
            ->with(self::INITIAL_ROUTE_NAME, $params, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expectedResult);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Pretty route not found', ['route_name' => 'some_entity_index']);

        $result = $this->testedClass->generate(self::INITIAL_ROUTE_NAME, $params);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array<int, mixed>
     */
    public function generateDataProvider(): array
    {
        return [
            [[], self::INITIAL_ROUTE_NAME, []],
            [
                [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityController',
                ],
                self::INITIAL_ROUTE_NAME,
                [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityController',
                ],
            ],
            [
                [
                    'crudAction' => 'index',
                ],
                self::INITIAL_ROUTE_NAME,
                [
                    'crudAction' => 'index',
                ],
            ],
            [
                [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'some_entity_index',
                [],
            ],
            [
                [
                    'pageNumber' => 12,
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'some_entity_index',
                [
                    'pageNumber' => 12,
                ],
            ],
        ];
    }
}
