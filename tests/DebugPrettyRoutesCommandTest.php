<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\Command\DebugPrettyRoutesCommand;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Command\DebugPrettyRoutesCommand
 */
class DebugPrettyRoutesCommandTest extends TestCase
{
    private PrettyRoutesLoader|MockObject $routesLoader;
    private DebugPrettyRoutesCommand $testedClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->routesLoader = $this->createMock(PrettyRoutesLoader::class);
        $this->testedClass = new DebugPrettyRoutesCommand($this->routesLoader);
    }

    /**
     * @throws Exception|ExceptionInterface
     */
    public function testExecution(): void
    {
        $resource = md5(random_bytes(random_int(6, 8)));
        $routes = new RouteCollection();
        $routes->add(
            name: 'routeName',
            route: new Route(
                path: '/path/to/name',
                defaults: [
                    PrettyUrlsGenerator::EA_FQCN => $resource,
                    PrettyUrlsGenerator::EA_ACTION => $resource,
                ]
            )
        );

        $this->routesLoader->expects(self::once())
            ->method('load')
            ->with($resource)
            ->willReturn($routes);

        $tester = new CommandTester($this->testedClass);
        $result = $tester->execute(['resource' => $resource]);

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($this->getFormatterResponse($resource), $tester->getDisplay());
    }

    private function getFormatterResponse(string $resource)
    {
        return <<<RESPONSE
+-----------+---------------+----------------------------------+----------------------------------+
| Name      | Path          | CRUD Controller                  | CRUD Action                      |
+-----------+---------------+----------------------------------+----------------------------------+
| routeName | /path/to/name | $resource | $resource |
+-----------+---------------+----------------------------------+----------------------------------+

RESPONSE;
    }
}
