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
use Eurotext\TranslationManagerProduct\Validator\WebsiteAssignmentValidator;
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

    /**
     * @var WebsiteAssignmentValidator
     */
    private $websiteAssignmentValidator;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProjectProductFactory $projectProductFactory,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        WebsiteAssignmentValidator $websiteAssignmentValidator,
        LoggerInterface $logger
    ) {
        $this->productRepository          = $productRepository;
        $this->projectProductFactory      = $projectProductFactory;
        $this->projectProductRepository   = $projectProductRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->websiteAssignmentValidator = $websiteAssignmentValidator;
        $this->logger                     = $logger;
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
            $this->logger->warning('no matching products found');

            return $result;
        }

        $entitiesNotFound = array_flip($entities);

        // create project product configurations
        $products = $searchResult->getItems();

        foreach ($products as $product) {
            // Found entity, so remove it from not found list
            unset($entitiesNotFound[$product->getSku()]);

            /** @var $product ProductInterface */
            $isSeeded = $this->seedEntity($project, $product);

            $result = !$isSeeded ? false : $result;
        }

        // Log entites that where not found
        if (count($entitiesNotFound) > 0) {
            foreach ($entitiesNotFound as $sku => $value) {
                $this->logger->error(sprintf('product-sku "%s" not found', $sku));
            }

        }

        return $result;
    }

    private function seedEntity(ProjectInterface $project, ProductInterface $product): bool
    {
        $projectId  = $project->getId();
        $productId  = (int)$product->getId();
        $productSku = $product->getSku();

        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::ENTITY_ID, $productId);
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->projectProductRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() >= 1) {
            // product has already been added to project
            $this->logger->info(sprintf('skipping product "%s"(%d) already added', $productSku, $productId));

            return true;
        }

        try {
            $isValid = $this->websiteAssignmentValidator->validate($project, $product);
            if (!$isValid) {
                $this->logger->error(
                    sprintf('product "%s"(%d) not assigned to store/website of project', $productSku, $productId)
                );

                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        /** @var ProjectProductInterface $projectProduct */
        $projectProduct = $this->projectProductFactory->create();
        $projectProduct->setProjectId($projectId);
        $projectProduct->setEntityId($productId);
        $projectProduct->setStatus(ProjectProductInterface::STATUS_NEW);

        try {
            $this->projectProductRepository->save($projectProduct);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
