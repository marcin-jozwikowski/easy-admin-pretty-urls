<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;

use function PHPUnit\Framework\at;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator
 */
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
    }

    public function testContextSetting(): void
    {
        $context = new RequestContext();
        $this->router->expects(self::once())
            ->method('setContext')
            ->with($context);
        $this->testedClass = new PrettyUrlsGenerator(
            router: $this->router,
            logger: $this->logger,
            prettyUrlsRoutePrefix: 'pretty',
            prettyUrlsIncludeMenuIndex: false,
        );

        $this->testedClass->setContext($context);
    }

    public function testContextGetting(): void
    {
        $context = new RequestContext();
        $this->router->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
        $this->testedClass = new PrettyUrlsGenerator(
            router: $this->router,
            logger: $this->logger,
            prettyUrlsRoutePrefix: 'pretty',
            prettyUrlsIncludeMenuIndex: false,
        );
        $result = $this->testedClass->getContext();

        self::assertEquals($context, $result);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $expectedParams
     *
     * @dataProvider generateDataProvider
     *
     * @throws Exception
     */
    public function testGenerate(string $prefix, array $params, string $expectedName, array $expectedParams, bool $includeMenuIndex): void
    {
        $expectedResult = base64_encode(random_bytes(16));
        $this->logger->expects(self::never())
            ->method('debug');
        $this->router->expects(self::once())
            ->method('generate')
            ->with($expectedName, $expectedParams, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expectedResult);
        $this->testedClass = new PrettyUrlsGenerator(
            router: $this->router,
            logger: $this->logger,
            prettyUrlsRoutePrefix: $prefix,
            prettyUrlsIncludeMenuIndex: $includeMenuIndex,
        );

        $result = $this->testedClass->generate(self::INITIAL_ROUTE_NAME, $params);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @throws Exception
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
            ->with('pretty_some_entity_index', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willThrowException(new RouteNotFoundException());

        $this->router->expects(at(1))
            ->method('generate')
            ->with(self::INITIAL_ROUTE_NAME, $params, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($expectedResult);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Pretty route not found', [
                'route_name' => 'pretty_some_entity_index',
                'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                'crudAction' => 'index',
            ]);
        $this->testedClass = new PrettyUrlsGenerator(
            router: $this->router,
            logger: $this->logger,
            prettyUrlsRoutePrefix: 'pretty',
            prettyUrlsIncludeMenuIndex: false,
        );

        $result = $this->testedClass->generate(self::INITIAL_ROUTE_NAME, $params);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array<int, mixed>
     */
    public function generateDataProvider(): array
    {
        return [
            'completelyEmpty' => [
                'prefix' => 'pretty',
                'params' => [],
                'expectedName' => self::INITIAL_ROUTE_NAME,
                'expectedParams' => [],
                'includeMenuIndex' => false,
            ],
            'onlyControllerNameProvided' => [
                'prefix' => 'pretty',
                'params' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityController',
                ],
                'expectedName' => self::INITIAL_ROUTE_NAME,
                'expectedParams' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityController',
                ],
                'includeMenuIndex' => false,
            ],
            'onlyActionProvided' => [
                'prefix' => 'pretty',
                'params' => [
                    'crudAction' => 'index',
                ],
                'expectedName' => self::INITIAL_ROUTE_NAME,
                'expectedParams' => [
                    'crudAction' => 'index',
                ],
                'includeMenuIndex' => false,
            ],
            'controllerAndActionProvided' => [
                'prefix' => 'pretty',
                'params' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'expectedName' => 'pretty_some_entity_index',
                'expectedParams' => [],
                'includeMenuIndex' => false,
            ],
            'additionalParameterProvided' => [
                'prefix' => 'pretty',
                'params' => [
                    'pageNumber' => 12,
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'expectedName' => 'pretty_some_entity_index',
                'expectedParams' => [
                    'pageNumber' => 12,
                ],
                'includeMenuIndex' => false,
            ],
            'nonDefaultPrefix' => [
                'prefix' => 'other_prefix',
                'params' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'expectedName' => 'other_prefix_some_entity_index',
                'expectedParams' => [],
                'includeMenuIndex' => false,
            ],
            'nonDefaultPrefixWithAdditionalParam' => [
                'prefix' => 'other_prefix',
                'params' => [
                    'pageNumber' => 12,
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                ],
                'expectedName' => 'other_prefix_some_entity_index',
                'expectedParams' => [
                    'pageNumber' => 12,
                ],
                'includeMenuIndex' => false,
            ],
            'menuIndexProvidedWhenDisabled' => [
                'prefix' => 'pretty',
                'params' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                    'menuIndex' => 1,
                    'submenuIndex' => 2,
                ],
                'expectedName' => 'pretty_some_entity_index',
                'expectedParams' => [
                    'menuIndex' => 1,
                    'submenuIndex' => 2,
                ],
                'includeMenuIndex' => false,
            ],
            'menuIndexProvidedAndEnabled' => [
                'prefix' => 'pretty',
                'params' => [
                    'crudControllerFqcn' => 'App\\Controller\\SomeEntityCrudController',
                    'crudAction' => 'index',
                    'menuIndex' => 1,
                    'submenuIndex' => 2,
                ],
                'expectedName' => 'pretty_some_entity_index',
                'expectedParams' => [
                    'menuPath' => '1,2',
                ],
                'includeMenuIndex' => true,
            ],
        ];
    }
}
