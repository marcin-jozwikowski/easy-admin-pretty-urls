<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Command\DumpRoutesDefinitions;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Command\DumpRoutesDefinitions
 */
class DumpRoutesDefinitionsCommandTest extends TestCase
{
    private PrettyRoutesLoader|MockObject $routesLoader;
    private DumpRoutesDefinitions $testedClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->routesLoader = $this->createMock(PrettyRoutesLoader::class);
        $this->testedClass = new DumpRoutesDefinitions($this->routesLoader, true);
    }

    /**
     * @throws Exception|ExceptionInterface
     */
    public function testExecution(): void
    {
        $resource = md5(random_bytes(random_int(6, 8)));
        $resource2 = md5(random_bytes(random_int(6, 8)));
        $resource3 = md5(random_bytes(random_int(6, 8)));
        $routes = new RouteCollection();
        $routes->add(
            name: 'routeName',
            route: new Route(
                path: '/path/to/name',
                defaults: [
                    PrettyUrlsGenerator::EA_FQCN => $resource,
                    PrettyUrlsGenerator::EA_ACTION => $resource2,
                    PrettyUrlsGenerator::MENU_PATH => $resource3,
                ],
            ),
        );

        $this->routesLoader->expects(self::once())
            ->method('load')
            ->with($resource)
            ->willReturn($routes);

        $tester = new CommandTester($this->testedClass);
        $result = $tester->execute(['resource' => $resource]);

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($this->getFormatterResponse($resource, $resource2, $resource3), $tester->getDisplay());
    }

    private function getFormatterResponse(string $resource, string $resource2, string $resource3)
    {
        return <<<RESPONSE
routeName:
  path: /path/to/name
  controller: 
  defaults:
      crudControllerFqcn: {$resource}
      crudAction: {$resource2}
      menuPath: {$resource3}


RESPONSE;
    }
}
