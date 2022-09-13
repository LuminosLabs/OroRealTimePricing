<?php

namespace Luminoslabs\OroRealTimePricing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'real_time';

    public const ENABLE = 'enable';
    public const FRONTEND_ENABLE = 'frontend_enable';

    public function __construct(
        private string $alias
    ) {
    }

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder($this->alias);

        SettingsBuilder::append($treeBuilder->getRootNode(), [
            self::ENABLE    => ['type' => 'boolean', 'value' => true],
            self::FRONTEND_ENABLE   => ['type' => 'boolean', 'value' => false],

        ]);

        return $treeBuilder;
    }

    public static function getConfigurationName(string $name): string
    {
        return Configuration::ALIAS . '.' . $name;
    }
}
