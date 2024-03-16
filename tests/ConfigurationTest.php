<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use Exception;
use MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetConfigTreeBuilder(): void
    {
        $normalizeValues = [
            'default_dashboard' => base64_encode(random_bytes(random_int(8, 16))),
            'include_menu_index' => (random_int(0, 16) % 2 === 0),
            'route_prefix' => base64_encode(random_bytes(random_int(8, 16))),
            'drop_entity_fqcn' => (random_int(0, 16) % 2 === 0),
            'default_actions' => [
                base64_encode(random_bytes(random_int(8, 16))),
                base64_encode(random_bytes(random_int(8, 16))),
            ],
        ];

        $configObject = new Configuration();
        $result = $configObject->getConfigTreeBuilder();
        self::assertInstanceOf(expected: TreeBuilder::class, actual: $result);
        $tree = $result->buildTree();
        self::assertInstanceOf(expected: NodeInterface::class, actual: $tree);
        self::assertEquals('easy_admin_pretty_urls', $tree->getName());
        self::assertEquals($normalizeValues, $tree->normalize($normalizeValues));
    }
}
