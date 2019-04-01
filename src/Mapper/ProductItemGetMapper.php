<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Eurotext\TranslationManagerProduct\ScopeConfig\ProductScopeConfigReader;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

class ProductItemGetMapper
{
    /**
     * @var ProductScopeConfigReader
     */
    private $productScopeConfig;

    public function __construct(ProductScopeConfigReader $productScopeConfig)
    {
        $this->productScopeConfig = $productScopeConfig;
    }

    public function map(ItemGetResponse $itemGetResponse, ProductInterface $product): ProductInterface
    {
        $item = $itemGetResponse->getItemData();

        $product->setName($item->getDataValue('name'));

        $attributesEnabled = $this->productScopeConfig->getAttributesEnabled();
        foreach ($attributesEnabled as $attributeCode) {
            $customAttribute = $product->getCustomAttribute($attributeCode);

            if ($customAttribute === null) {
                // missing custom attribute, maybe due to not being set at the global product,
                // or the attribute has been removed but still is configured in the system.xml
                continue;
            }

            $newValue = $item->getDataValue($attributeCode);

            if (empty($newValue)) {
                // If there is no translated value do not set it
                continue;
            }

            $customAttribute->setValue($newValue);
        }

        // Unset UrlKey so it gets generated automatically
        /** @var $product Product */
        $product->setUrlKey('');
        $urlKeyAttribute = $product->getCustomAttribute('url_key');
        if ($urlKeyAttribute !== null) {
            $urlKeyAttribute->setValue(null);
        }

        return $product;
    }
}