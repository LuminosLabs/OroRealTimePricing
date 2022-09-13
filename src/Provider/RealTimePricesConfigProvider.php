<?php

namespace Luminoslabs\OroRealTimePricing\Provider;

use Luminoslabs\OroRealTimePricing\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class RealTimePricesConfigProvider
{
    public function __construct(
        protected ConfigManager $configManager,
    )
    {
    }

    /**
     * @return bool
     */
    public function isRealTimePricesEnable(): bool
    {
        return $this->configManager->get(
            Configuration::getConfigurationName(
                Configuration::FRONTEND_ENABLE
            )
        );
    }
}
