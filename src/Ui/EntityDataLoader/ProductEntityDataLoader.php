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
use Magento\Catalog\Api\Data\ProductInterface;
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

        $projectProductsArray = $projectProducts->getItems();

        // Load Product Information
        $productIds = array_map(
            function (ProjectProductInterface $projectProduct) {
                return $projectProduct->getEntityId();
            }, $projectProductsArray
        );
        $this->productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $this->productCollection->addAttributeToSelect('name');
        $this->productCollection->load();

        $productsArray = [];
        foreach ($projectProductsArray as $key => $projectProduct) {
            /** @var $projectProduct ProjectProductInterface */
            $productData = $projectProduct->toArray();

            /** @var ProductInterface $product */
            $product = $this->productCollection->getItemById($projectProduct->getEntityId());

            if ($product instanceof ProductInterface) {
                $productData['sku']  = $product->getSku();
                $productData['name'] = $product->getName();
            }
            $productData['position'] = $key;

            $productsArray[$key] = $productData;
        }

        // use array_values to have the array start at 0
        // otherwise the dyanmicsRow Grid will fail, cause it expects the array to start at 0
        $data['products'] = array_values($productsArray);

        return true;
    }
}