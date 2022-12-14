<?php

namespace Luminoslabs\OroRealTimePricing\Provider;

use Luminoslabs\OroRealTimePricing\DependencyInjection\Configuration;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

class RealTimeProductPriceProvider implements ProductPriceProviderInterface
{
    use MemoryCacheProviderAwareTrait;

    protected ApiProviderInterface $apiProvider;

    const FRONTEND_ONLY_ROUTES = [
        'oro_product_frontend_product_view',
        'oro_frontend_root',
        'oro_shopping_list_frontend_update',
        'oro_shopping_list_frontend_index',
        'oro_shopping_list_frontend_view',
        'oro_checkout_frontend_checkout',
        'oro_cms_frontend_page_view',
        'oro_product_frontend_product_index'
    ];

    public function __construct(
        protected ProductPriceStorageInterface $priceStorage,
        protected UserCurrencyManager          $currencyManager,
        protected ConfigManager                $configManager,
        private RequestStack                   $requestStack,
        iterable                               $apiProviders
    )
    {
        $this->apiProvider = iterator_to_array($apiProviders)[0];
    }

    /**
     * @return mixed|null
     */
    public function isActive()
    {
        $isFrontendEnabled = $this->configManager->get(
            Configuration::getConfigurationName(
                Configuration::FRONTEND_ENABLE
            )
        );
        $isBackendEnabled = $this->configManager->get(
            Configuration::getConfigurationName(
                Configuration::ENABLE
            )
        );

        $routeName = $this->requestStack->getMainRequest()->attributes->get('_route');
        if ($isFrontendEnabled
            && $isBackendEnabled
            && in_array($routeName, self::FRONTEND_ONLY_ROUTES)) {
            return false;
        }

        return $isBackendEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria): array
    {
        return array_intersect(
            $this->currencyManager->getAvailableCurrencies(),
            $this->priceStorage->getSupportedCurrencies($scopeCriteria)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesByScopeCriteriaAndProducts(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array                              $products,
        array                              $currencies,
        string                             $unitCode = null
    ): array
    {
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        if (empty($currencies)) {
            // There is no sense to get prices because of no allowed currencies present.
            return [];
        }

        $productsIds = [];
        foreach ($products as $product) {
            $productId = is_a($product, Product::class) ? $product->getId() : (int)$product;
            $productsIds[$productId] = $productId;
        }

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $prices = $this->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);
        $this->sortPrices($prices);

        $result = [];
        foreach ($prices as $price) {
            $result[$price->getProduct()->getId()][] = $price;
        }

        return $result;
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productsIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     *
     * @return ProductPriceInterface[]
     */
    protected function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array                              $productsIds,
        array                              $productUnitCodes = null,
        array                              $currencies = null
    ): array
    {
        if (!$currencies) {
            // There is no sense to get prices when no allowed currencies present.
            return [];
        }

        return ($this->isActive()) ?
            $this->apiProvider->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies) :
            $this->getCachedPrices($scopeCriteria, $productsIds, $currencies, $productUnitCodes);
    }

    private function getCachedPrices($scopeCriteria, $productsIds, $currencies, $productUnitCodes)
    {
        return (array)$this->getMemoryCacheProvider()->get(
            [
                'product_price_scope_criteria' => $scopeCriteria,
                $productsIds,
                $currencies,
                $productUnitCodes,
            ],
            function () use ($scopeCriteria, $productsIds, $productUnitCodes, $currencies) {
                $prices = $this->priceStorage->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);
                $this->sortPrices($prices);

                return $prices;
            }
        );
    }

    /**
     * @param array $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return Price[]
     */
    public function getMatchedPrices(
        array                              $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array
    {
        return $this->getActualMatchedPrices($productPriceCriteria, $scopeCriteria);
    }

    /**
     * @param array $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return Price[]
     */
    protected function getActualMatchedPrices(
        array                              $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array
    {
        $productsIds = [];
        $productUnitCodes = [];
        $currencies = [];
        $result = [];

        /** @var ProductPriceCriteria $productPriceCriterion */
        foreach ($productPriceCriteria as $productPriceCriterion) {
            $productUnitCode = $productPriceCriterion->getProductUnit()->getCode();
            $currency = $productPriceCriterion->getCurrency();
            $productId = $productPriceCriterion->getProduct()->getId();

            $productsIds[$productId] = $productId;
            $productUnitCodes[$productUnitCode] = $productUnitCode;
            $currencies[$currency] = $currency;
        }

        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        $prices = $this->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);

        $productPriceData = [];
        foreach ($prices as $priceData) {
            $key = $this->getKey(
                $priceData->getProduct(),
                $priceData->getUnit(),
                $priceData->getPrice()->getCurrency()
            );

            $productPriceData[$key][] = $priceData;
        }

        foreach ($productPriceCriteria as $productPriceCriterion) {
            $quantity = $productPriceCriterion->getQuantity();
            $currency = $productPriceCriterion->getCurrency();
            $key = $this->getKey(
                $productPriceCriterion->getProduct(),
                $productPriceCriterion->getProductUnit(),
                $currency
            );

            $price = $this->matchPriceByQuantity($productPriceData[$key] ?? [], $quantity);

            $identifier = $productPriceCriterion->getIdentifier();
            $result[$identifier] = $price !== null ? Price::create($price, $currency) : null;
        }

        return $result;
    }

    /**
     * @param ProductPriceInterface[] $prices
     */
    private function sortPrices(array &$prices)
    {
        usort($prices, static function (ProductPriceDTO $a, ProductPriceDTO $b) {
            $codeA = $a->getUnit()->getCode();
            $codeB = $b->getUnit()->getCode();
            if ($codeA === $codeB) {
                return $a->getQuantity() <=> $b->getQuantity();
            }

            return $codeA <=> $codeB;
        });
    }

    private function getKey(Product $product, MeasureUnitInterface $unit, string $currency): string
    {
        return sprintf('%s|%s|%s', $product->getId(), $unit->getCode(), $currency);
    }

    /**
     * @param array|ProductPriceInterface[] $pricesData
     * @param float $expectedQuantity
     * @return float|null
     */
    protected function matchPriceByQuantity(array $pricesData, $expectedQuantity): ?float
    {
        $price = null;
        foreach ($pricesData as $priceData) {
            $quantity = $priceData->getQuantity();

            if ($expectedQuantity >= $quantity) {
                $price = $priceData->getPrice()->getValue();
            }

            if ($expectedQuantity <= $quantity) {
                // Matching price has been already found, break from loop.
                break;
            }
        }

        return $price;
    }

    /**
     * Restrict currencies list to getSupportedCurrencies
     */
    protected function getAllowedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria, array $currencies): array
    {
        if (empty($currencies)) {
            return $currencies;
        }

        return array_intersect($currencies, $this->getSupportedCurrencies($scopeCriteria));
    }
}
