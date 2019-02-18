<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Service;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class CleanProductsService
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->logger                   = $logger;
    }

    /**
     * Delete project entity assignements for entitys not in the entityIds array.
     *
     * This method assumes the array in $entityIds contains an array of all entities that are wanted.
     * All entities not in this array will be deleted
     *
     * @param ProjectInterface $project
     * @param array $entityIds
     *
     * @return bool
     */
    public function cleanMissingByIds(ProjectInterface $project, array $entityIds): bool
    {
        $projectId = $project->getId();

        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
        if (count($entityIds) > 0) {
            $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::ID, $entityIds, 'nin');
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->projectProductRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() === 0) {
            return true;
        }

        foreach ($searchResults->getItems() as $projectProduct) {
            /** @var $projectProduct ProjectProductInterface */
            try {
                $this->projectProductRepository->delete($projectProduct);
            } catch (\Exception $e) {
                $projectEntityId = $projectProduct->getId();

                $this->logger->error(
                    sprintf('product "%s" not removed from project "%s"', $projectEntityId, $projectId)
                );
            }
        }

        return true;
    }
}