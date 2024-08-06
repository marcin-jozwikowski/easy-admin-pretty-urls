<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests\Routing;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsResolver
 */
class PrettyUrlsResolverTest extends TestCase
{
    private RouterInterface|MockObject $router;
    private PrettyUrlsResolver $tested;

    public function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->tested = new PrettyUrlsResolver($this->router);
    }

    /**
     * @dataProvider domainsProvider
     */
    public function testResolveToParams(string $domain): void
    {
        $queryParam = 'p'.random_int(1, 2000);
        $queryValue = 'v'.random_int(1, 2000);
        $routeParam = 'p'.random_int(1, 2000);
        $routeValue = 'v'.random_int(1, 2000);

        $path = '/some/path/'.$routeValue;
        $url = $domain.$path.'?'.$queryParam.'='.$queryValue;

        $this->router->expects(self::once())
            ->method('match')
            ->with($path)
            ->willReturn([$routeParam => $routeValue]);

        $values = $this->tested->resolveToParams($url);

        self::assertArrayHasKey(key: $queryParam, array: $values);
        self::assertArrayHasKey(key: $routeParam, array: $values);
    }

    public function domainsProvider()
    {
        return [
            [''],
            ['http://some.dom'],
            ['https://other.one'],
        ];
    }
}
