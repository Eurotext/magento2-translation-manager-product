<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Ui\DataProvider;

use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ProjectProductDataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     * @since 101.0.0
     */
    protected $request;

    /**
     * @var ProductRepositoryInterface
     * @since 101.0.0
     */
    protected $productRepository;

    /**
     * @var StoreRepositoryInterface
     * @since 101.0.0
     */
    protected $storeRepository;

    /**
     * @var ProjectProductRepositoryInterface
     */
    private $projectProductRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        StoreRepositoryInterface $storeRepository,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );

        $this->collection               = $collectionFactory->create();
        $this->request                  = $request;
        $this->productRepository        = $productRepository;
        $this->storeRepository          = $storeRepository;
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        $data = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items'        => array_values($items),
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getCollection()
    {
        /** @var Collection $collection */
        $collection = parent::getCollection();
        $collection->addAttributeToSelect('status');

        if (!$this->getProjectId()) {
            return $collection;
        }

        return $this->addCollectionFilters($collection);
    }

    protected function addCollectionFilters(Collection $collection): Collection
    {
        $existingRelations = [];

        $projectId = $this->getProjectId();

        $this->searchCriteriaBuilder->addFilter('project_id', $projectId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->projectProductRepository->getList($searchCriteria);

        foreach ($searchResults as $projectProduct) {
            /** @var ProjectProductInterface $projectProduct */
            $productId = $projectProduct->getId();

            $product = $this->productRepository->getById($productId);

            $existingRelations[] = $product->getId();
        }

        if ($existingRelations) {
            $collection->addAttributeToFilter($collection->getIdFieldName(), ['nin' => [$existingRelations]]);
        }

        return $collection;
    }

    protected function getProjectId()
    {
        return $this->request->getParam('current_project_id');
    }

    protected function getStoreId()
    {
        return $this->request->getParam('current_store_id');
    }
}
