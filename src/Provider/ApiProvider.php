<?php

namespace Luminoslabs\OroRealTimePricing\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class ApiProvider implements ApiProviderInterface
{
    public function __construct(
        protected DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        array $productUnitCodes = null,
        array $currencies = null
    ):array
    {
        $prices = $this->getRealTimeProductPrices($productIds, $scopeCriteria->getCustomer());
        $result = [];
        if ($currencies) {
            foreach ($prices as $productId => $product) {
                foreach ($product['prices'] as $prices) {
                    $product = $this->getProduct($productId);
                    $price = Price::create($prices['price'], array_values($currencies)[0]);
                    $unit = $this->doctrineHelper->getEntityManagerForClass(ProductUnit::class)
                        ->getReference(ProductUnit::class, 'item');//'item' should be valued from API
                    $result[] = new ProductPriceDTO($product, $price, (float)$prices['qty'], $unit);

                }
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @return mixed|object|null
     */
    private function getProduct($id)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(Product::class)->findOneBy([
            'id' => $id
        ]);
    }


    /**
     * @hardcoded all prices
     *
     * @param $productsIds
     * @param $customer
     * @return array
     */
    public function getRealTimeProductPrices($productsIds, $customer)
    {
        $result = [];
        foreach ($productsIds as $id) {
            $result[$id] = array(
                'prices'    => array(
                    array(
                        'qty' => 1,
                        'price' => 25
                    ),
                    array(
                        'qty' => 10,
                        'price' => 20
                    ),
                    array(
                        'qty' => 20,
                        'price' => 15
                    )
                )
            );
        }

        return $result;
    }
}
