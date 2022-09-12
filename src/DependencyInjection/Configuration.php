<?php

namespace Luminoslabs\OroRealTimePricing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    public const ENABLE_BACKEND = 'enable_backend';
    public const ENABLE_FRONTEND = 'enable_frontend';

    public const ALIAS = 'real_time';


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
            self::ENABLE_BACKEND    => ['type' => 'boolean', 'value' => true],
            self::ENABLE_FRONTEND   => ['type' => 'boolean', 'value' => true],
        ]);

        return $treeBuilder;
    }

    public static function getConfigurationName(string $name): string
    {
        return Configuration::ALIAS . '.' . $name;
    }
}
