<?php

namespace Luminoslabs\OroRealTimePricing\Provider;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

interface ApiProviderInterface
{
    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     * @return ProductPriceDTO[]
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array                              $productIds,
        array                              $productUnitCodes = null,
        array                              $currencies = null
    ): array;

}
