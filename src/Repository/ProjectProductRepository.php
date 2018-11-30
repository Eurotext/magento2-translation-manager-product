<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerProduct\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectEntityInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Model\ProjectProduct;
use Eurotext\TranslationManagerProduct\Model\ProjectProductFactory;
use Eurotext\TranslationManagerProduct\Model\ResourceModel\ProjectProductCollectionFactory;
use Eurotext\TranslationManagerProduct\Model\ResourceModel\ProjectProductResource;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProjectProductRepository implements ProjectProductRepositoryInterface
{
    /**
     * @var ProjectProductFactory
     */
    protected $projectProductFactory;

    /**
     * @var ProjectProductResource
     */
    private $productResource;

    /**
     * @var ProjectProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    public function __construct(
        ProjectProductResource $productResource,
        ProjectProductFactory $projectFactory,
        ProjectProductCollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->projectProductFactory = $projectFactory;
        $this->productResource       = $productResource;
        $this->collectionFactory     = $collectionFactory;
        $this->searchResultsFactory  = $searchResultsFactory;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return ProjectEntityInterface
     * @throws CouldNotSaveException
     */
    public function save(ProjectEntityInterface $object): ProjectEntityInterface
    {
        try {
            /** @var ProjectProduct $object */
            $this->productResource->save($object);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param int $id
     *
     * @return ProjectEntityInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ProjectEntityInterface
    {
        /** @var ProjectProduct $object */
        $object = $this->projectProductFactory->create();
        $this->productResource->load($object, $id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Project with id "%1" does not exist.', $id));
        }

        return $object;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ProjectEntityInterface $object): bool
    {
        try {
            /** @var ProjectProduct $object */
            $this->productResource->delete($object);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        $object = $this->getById($id);

        return $this->delete($object);
    }

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC';
                $collection->addOrder($sortOrder->getField(), $direction);
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }

        /** @var \Magento\Framework\Api\SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
