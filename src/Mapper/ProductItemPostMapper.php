<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerProduct\Mapper;

use Eurotext\RestApiClient\Request\Data\Project\ItemData;
use Eurotext\RestApiClient\Request\Project\ItemDataRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Eurotext\TranslationManagerProduct\ScopeConfig\ProductScopeConfigReader;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */
class ProductItemPostMapper
{
    const ENTITY_TYPE = 'product';

    /**
     * @var ScopeConfigReaderInterface
     */
    private $scopeConfig;

    /**
     * @var ProductScopeConfigReader
     */
    private $productScopeConfig;

    public function __construct(
        ScopeConfigReaderInterface $scopeConfigReader,
        ProductScopeConfigReader $productScopeConfig
    ) {
        $this->scopeConfig        = $scopeConfigReader;
        $this->productScopeConfig = $productScopeConfig;
    }

    public function map(ProductInterface $product, ProjectInterface $project): ItemDataRequest
    {
        $projectId = $project->getId();

        $languageSrc  = $this->scopeConfig->getLocaleForStore($project->getStoreviewSrc());
        $languageDest = $this->scopeConfig->getLocaleForStore($project->getStoreviewDst());

        $attributesEnabled = $this->productScopeConfig->getAttributesEnabled();

        $data = [
            'name' => $product->getName(),
        ];

        foreach ($attributesEnabled as $attributeCode) {
            $customAttribute = $product->getCustomAttribute($attributeCode);

            if ($customAttribute === null) {
                continue;
            }

            $data[$attributeCode] = $customAttribute->getValue();
        }

        $meta = [
            'item_id'     => $product->getId(),
            'entity_id'   => $product->getId(),
            'entity_type' => self::ENTITY_TYPE,
        ];

        $itemData = new ItemData($data, $meta);

        $itemRequest = new ItemDataRequest(
            $projectId,
            $languageSrc,
            $languageDest,
            self::ENTITY_TYPE,
            '',
            $itemData
        );

        return $itemRequest;
    }
}