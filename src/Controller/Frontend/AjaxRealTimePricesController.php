<?php

namespace Luminoslabs\OroRealTimePricing\Controller\Frontend;

use Luminoslabs\OroRealTimePricing\Provider\ApiProviderInterface;
use Luminoslabs\OroRealTimePricing\Service\PricesHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;

class AjaxRealTimePricesController extends AbstractController
{
    public function __construct(
        iterable                                        $apiProviders,
        private ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler,
        private ConfigManager                           $configManager,
        private RequestStack                            $requestStack,
        private PricesHelper                            $pricesHelper
    )
    {
        $this->apiProvider = iterator_to_array($apiProviders)[0];
    }

    /**
     * @Route("/get-prices", name="real_time_frontend_price", methods={"GET"})
     *
     */
    public function getRealTimePricesAction(): JsonResponse
    {
        $productIds = (array)$this->requestStack->getMainRequest()->query->get('product_ids');
        $realTimePrices = $this->apiProvider->getPrices(
            $this->priceScopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $productIds,
            null,
            [$this->getDefaultCurrency()]
        );

        return new JsonResponse($this->pricesHelper->getPricesAsArray($realTimePrices));
    }

    /**
     * @param array $realTimePrices
     * @return array
     * @Route("/datagrid/get-prices", name="datagrid_real_time_frontend_price", methods={"GET"})
     */
    public function getRealTimePricesForDatagridAction(): JsonResponse
    {
        $productQuantities = (array)$this->requestStack->getMainRequest()->query->get('product_quantities');
        $productIds = array_keys($productQuantities);
        $realTimePrices = $this->apiProvider->getPrices(
            $this->priceScopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $productIds,
            null,
            [$this->getDefaultCurrency()]
        );

        return new JsonResponse($this->pricesHelper->buildDataGridPrices($realTimePrices, $productQuantities));
    }

    /**
     * @return string
     */
    private function getDefaultCurrency(): string
    {
        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);
        return $this->configManager->get($currencyConfigKey) ?: CurrencyConfig::DEFAULT_CURRENCY;
    }
}
