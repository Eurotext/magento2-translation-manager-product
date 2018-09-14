<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerProduct\Mapper;

use Eurotext\RestApiClient\Request\Data\Project\ItemData;
use Eurotext\RestApiClient\Request\Project\ItemDataRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */
class ProductItemPostMapper
{
    const CONFIG_PATH_LOCALE_CODE = 'general/locale/code';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function map(ProductInterface $product, ProjectInterface $project): ItemDataRequest
    {
        $projectId     = $project->getId();
        $scopeCodeSrc  = $project->getStoreviewSrc();
        $scopeCodeDest = $project->getStoreviewDst();

        // @todo refactor this to Eurotext\TranslationManagerProduct\System\ScopeConfig
        $languageSrc  = $this->scopeConfig->getValue(self::CONFIG_PATH_LOCALE_CODE, 'stores', $scopeCodeSrc);
        $languageDest = $this->scopeConfig->getValue(self::CONFIG_PATH_LOCALE_CODE, 'stores', $scopeCodeDest);

        $data = [
            'name' => $product->getName(),
            // @todo get attributes to map
        ];
        $meta = [
            'item_id'   => $product->getId(),
            'entity_id' => $product->getId(),
        ];

        $itemData = new ItemData($data, $meta);

        $itemRequest = new ItemDataRequest(
            $projectId,
            $languageSrc,
            $languageDest,
            'product',
            '',
            $itemData
        );

        return $itemRequest;
    }
}