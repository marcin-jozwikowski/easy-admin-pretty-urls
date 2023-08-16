<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use MarcinJozwikowski\EasyAdminPrettyUrls\Twig\PrettyUrlsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

use function PHPUnit\Framework\assertEquals;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Twig\PrettyUrlsExtension
 */
class PrettyUrlsExtensionTest extends TestCase
{
    private PrettyUrlsGenerator|MockObject $generator;
    private PrettyUrlsExtension $tested;

    public function setUp(): void
    {
        $this->generator = $this->createMock(PrettyUrlsGenerator::class);
        $this->tested = new PrettyUrlsExtension($this->generator);
    }

    public function testGetFilters(): void
    {
        $result = $this->tested->getFilters();
        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertInstanceOf(TwigFilter::class, $result[0]);
        self::assertEquals('pretty_urls_remove_actions', $result[0]->getName());
        self::assertIsArray($result[0]->getCallable());
        self::assertEquals($this->tested, $result[0]->getCallable()[0]);
        self::assertEquals('prettyUrlsRemoveActions', $result[0]->getCallable()[1]);
    }

    /**
     * @dataProvider removeActionData
     */
    public function testRemoveAction(string $url, string $expected, ?array $sanitizeArguments, string $sanitizeResult): void
    {
        if ($sanitizeArguments) {
            $this->generator->method('sanitizeUrl')
                ->with(...$sanitizeArguments)
                ->willReturn($sanitizeResult);
        }

        $result = $this->tested->prettyUrlsRemoveActions($url);

        assertEquals($expected, $result);
    }

    public function removeActionData(): array
    {
        $randomPath = substr(sha1(random_bytes(8)), 1, 4).'/'.substr(sha1(random_bytes(8)), 1, 6);

        return [
            ['', '', null, ''],
            ['https://some.url', 'https://some.url', null, ''],
            ['/some/path', '/some/path', null, ''],
            ['/some/path?page=12', '/some/path?page=12', null, ''],
            ['/some/path?page=12&referrer=', '/some/path?page=12&referrer=', null, ''],
            ['/some/path?page=12&referrer=/ref', '/some/path?page=12&referrer='.$randomPath, ['/ref'], $randomPath],
            ['/some/path?page=12&referrer=/ref?action=index', '/some/path?page=12&referrer='.$randomPath, ['/ref?action=index'], $randomPath],
            ['/some/path?page=12&referrer=/'.$randomPath, '/some/path?page=12&referrer='.$randomPath, ['/'.$randomPath], $randomPath],
        ];
    }
}
