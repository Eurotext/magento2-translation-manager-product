<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Seeder;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySeederInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Model\ProjectProductFactory;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * ProductSeeder
 */
class ProductSeeder implements EntitySeederInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProjectProductFactory
     */
    private $projectProductFactory;

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
        ProductRepositoryInterface $productRepository,
        ProjectProductFactory $projectProductFactory,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->productRepository        = $productRepository;
        $this->projectProductFactory    = $projectProductFactory;
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->logger                   = $logger;
    }

    public function seed(ProjectInterface $project, array $entities = []): bool
    {
        $result = true;

        // get product collection
        if (count($entities) > 0) {
            $this->searchCriteriaBuilder->addFilter('sku', $entities, 'in');
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->productRepository->getList($searchCriteria);

        if ($searchResult->getTotalCount() === 0) {
            // no products found, matching the criteria
            $this->logger->warning('no products found, matching the criteria');

            return $result;
        }

        $entitiesNotFound = array_flip($entities);

        // create project product configurations
        $products = $searchResult->getItems();

        $projectId = $project->getId();
        foreach ($products as $product) {
            /** @var $product ProductInterface */
            $productId  = (int)$product->getId();
            $productSku = $product->getSku();

            // Found entity, so remove it from not found list
            unset($entitiesNotFound[$productSku]);

            $this->searchCriteriaBuilder
                ->addFilter(ProjectProductSchema::ENTITY_ID, $productId)
                ->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
            $searchCriteria = $this->searchCriteriaBuilder->create();

            $searchResults = $this->projectProductRepository->getList($searchCriteria);

            if ($searchResults->getTotalCount() >= 1) {
                // product has already been added to project
                $this->logger->info(sprintf('skipping product-id:%d already added', $productId));
                continue;
            }

            /** @var ProjectProductInterface $projectProduct */
            $projectProduct = $this->projectProductFactory->create();
            $projectProduct->setProjectId($projectId);
            $projectProduct->setEntityId($productId);
            $projectProduct->setStatus(ProjectProductInterface::STATUS_NEW);

            try {
                $this->projectProductRepository->save($projectProduct);
            } catch (\Exception $e) {
                $result = false;
            }
        }

        // Log entites that where not found
        if (count($entitiesNotFound) > 0) {
            foreach ($entitiesNotFound as $sku => $value) {
                $this->logger->error(sprintf('product-sku "%s" not found', $sku));
            }

        }

        return $result;
    }
}
