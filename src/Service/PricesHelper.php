<?php

namespace Luminoslabs\OroRealTimePricing\Service;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;

class PricesHelper
{
    public function __construct(
        private NumberFormatter             $numberFormatter,
        private UnitValueFormatterInterface $unitValueFormatter,
        private UnitLabelFormatterInterface $unitLabelFormatter
    )
    {
    }

    public function getPricesAsArray(array $productPriceDTOs): array
    {
        $result = [];

        foreach ($productPriceDTOs as $productPriceDTO) {
            $priceValue = $productPriceDTO->getPrice()->getValue();
            $priceCurrency = $productPriceDTO->getPrice()->getCurrency();
            $unit = $productPriceDTO->getUnit()->getCode();
            $quantity = $productPriceDTO->getQuantity();
            $result[$productPriceDTO->getProduct()->getId()][] = [
                'currency' => $priceCurrency,
                'formatted_price' => $this->numberFormatter->formatCurrency($priceValue, $priceCurrency),
                'formatted_unit' => $this->unitLabelFormatter->format($unit),
                'price' => $priceValue,
                'quantity' => $quantity,
                'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unit),
                'unit' => $unit,
            ];
        }

        return $result;
    }

    public function buildDataGridPrices(array $productPriceDTOs, array $productQuantities): array
    {
        $dataGridPrices = [];
        $quantityBreaks = $this->getQuantityBreaks($productPriceDTOs);
        $total = 0;

        foreach ($quantityBreaks as $productId => $quantityBreak) {
            $priceValue = $this->getPriceByQuantity($quantityBreaks[$productId], $productQuantities[$productId]);
            $subtotal = $priceValue * $productQuantities[$productId];
            $total += $subtotal;
            $currency = $quantityBreaks[$productId][0]['currency'];
            $dataGridPrices['line_items'][$productId] = [
                'unit_price' => $this->numberFormatter->formatCurrency($priceValue, $currency),
                'subtotal' => $this->numberFormatter->formatCurrency($subtotal, $currency),
            ];
        }
        $dataGridPrices['totals'] = [
            'total_value' => $total,
            'total' => $this->numberFormatter->formatCurrency($total, $currency)
        ];

        return $dataGridPrices;
    }

    private function getQuantityBreaks(array $productPriceDTOs): array
    {
        $result = [];

        foreach ($productPriceDTOs as $productPriceDTO) {
            $result[$productPriceDTO->getProduct()->getId()][] = [
                'price' => $productPriceDTO->getPrice()->getValue(),
                'quantity' => $productPriceDTO->getQuantity(),
                'currency' => $productPriceDTO->getPrice()->getCurrency()
            ];
        }

        return $result;
    }

    private function mergeQuantityBreaks(array $quantityBreaks): array
    {
        $mergedQuantityBreaks = [];

        foreach ($quantityBreaks as $quantityBreak) {
            $mergedQuantityBreaks[$quantityBreak['quantity']] = $quantityBreak['price'];
        }

        return $mergedQuantityBreaks;
    }

    private function getPriceByQuantity(array $quantityBreaks, int $quantity): float
    {
        $mergedQuantityBreaks = $this->mergeQuantityBreaks($quantityBreaks);
        $quantities = array_keys($mergedQuantityBreaks);
        asort($quantities);
        $closestQuantity = $this->getClosest($quantity, $quantities);
        return $mergedQuantityBreaks[$closestQuantity];

    }

    public function getClosest(int $search, array $items): int
    {
        if (in_array($search, $items)) {
            return $search;
        }

        $itemsLength = count($items);
        $lastItem = $items[$itemsLength - 1];

        if ($search >= $lastItem) {
            return $lastItem;
        }

        for ($i = 0, $j = 1; $i <= $itemsLength - 2, $j <= $itemsLength - 1; $i++, $j++) {
            if (($items[$i] <= $search) && ($search < $items[$j])) {
                return $items[$i];
            }
        }

        return $items[0];
    }
}
