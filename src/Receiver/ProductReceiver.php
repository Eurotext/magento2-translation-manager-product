<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Receiver;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\RestApiClient\Request\Project\ItemGetRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntityReceiverInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\ScopeConfig\ProductScopeConfigReader;
use Eurotext\TranslationManagerProduct\Setup\EntitySchema\ProjectProductSchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductReceiver implements EntityReceiverInterface
{
    /**
     * @var ProjectProductRepositoryInterface
     */
    private $projectProductRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ItemV1ApiInterface
     */
    private $itemApi;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductScopeConfigReader
     */
    private $productScopeConfig;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ProductScopeConfigReader $productScopeConfig,
        LoggerInterface $logger
    ) {
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->itemApi                  = $itemApi;
        $this->productRepository        = $productRepository;
        $this->productScopeConfig       = $productScopeConfig;
        $this->logger                   = $logger;
    }

    public function receive(ProjectInterface $project): bool
    {
        $result = true;

        $projectId    = $project->getId();
        $projectExtId = $project->getExtId();
        $storeId      = $project->getStoreviewDst();

        $this->logger->info(sprintf('receive project products project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::EXT_ID, 0, 'gt');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectProductRepository->getList($searchCriteria);

        $projectProducts = $searchResult->getItems();

        foreach ($projectProducts as $projectProduct) {
            /** @var $projectProduct ProjectProductInterface */
            $itemExtId = $projectProduct->getExtId();
            $productId = $projectProduct->getProductId();

            try {
                $product = $this->productRepository->getById($productId, true, $storeId);

                $itemRequest = new ItemGetRequest($projectExtId, $itemExtId);

                $itemGetResponse = $this->itemApi->get($itemRequest);

                $item = $itemGetResponse->getItemData();

                // @todo refactor mapping to a processor class
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

                $this->productRepository->save($product);

                // @todo set status to imported
                $this->projectProductRepository->save($projectProduct);

                $this->logger->info(sprintf('product id:%d, ext-id:%d => success', $productId, $itemExtId));
            } catch (GuzzleException $e) {
                $message = $e->getMessage();
                $this->logger->error(sprintf('product id:%d => %s', $productId, $message));
                $result = false;
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $this->logger->error(sprintf('product id:%d => %s', $productId, $message));
                $result = false;
            }

        }

        return $result;
    }
}
