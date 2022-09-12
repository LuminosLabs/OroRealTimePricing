<?php

namespace Luminoslabs\OroRealTimePricing\Controller\Frontend;

use Luminoslabs\OroRealTimePricing\Provider\ApiProviderInterface;
use Luminoslabs\OroRealTimePricing\Service\PricesHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;

class AjaxRealTimePricesController extends AbstractController
{
    public function __construct(
        iterable             $apiProviders,
        private RequestStack $requestStack,
        private PricesHelper $pricesHelper,
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
            new ProductPriceScopeCriteria(),
            $productIds,
            null,
            ['USD']
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
            new ProductPriceScopeCriteria(),
            $productIds,
            null,
            ['USD']
        );

        return new JsonResponse($this->pricesHelper->buildDataGridPrices($realTimePrices, $productQuantities));
    }
}
