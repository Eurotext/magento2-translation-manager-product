<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Ui\EntityDataLoader;

use Eurotext\TranslationManager\Api\EntityDataLoaderInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductEntityDataLoader implements EntityDataLoaderInterface
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
     * @var ProductCollection
     */
    private $productCollection;

    public function __construct(
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->productCollection        = $productCollectionFactory->create();
    }

    public function load(int $projectId, array &$data): bool
    {
        $this->searchCriteriaBuilder->addFilter('project_id', $projectId);

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $projectProducts = $this->projectProductRepository->getList($searchCriteria);

        if ($projectProducts->getTotalCount() === 0) {
            $data['products'] = null;

            return true;
        }

        $productIds = [];
        foreach ($projectProducts->getItems() as $projectProduct) {
            /** @var $projectProduct ProjectProductInterface */
            $productId = $projectProduct->getEntityId();

            $productIds[$productId] = $productId;
        }

        $this->productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $this->productCollection->load();
        $productsArray = $this->productCollection->toArray();

        // use array_values to have the array start at 0
        // otherwise the dyanmicsRow Grid will fail, cause it expects the array to start at 0
        $data['products'] = array_values($productsArray);

        return true;
    }
}