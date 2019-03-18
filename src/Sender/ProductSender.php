<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySenderInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Mapper\ProductItemPostMapper;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class ProductSender implements EntitySenderInterface
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
     * @var ProductItemPostMapper
     */
    private $itemPostMapper;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ProductItemPostMapper $itemPostMapper,
        LoggerInterface $logger
    ) {
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->itemApi = $itemApi;
        $this->productRepository = $productRepository;
        $this->itemPostMapper = $itemPostMapper;
        $this->logger = $logger;
    }

    public function send(ProjectInterface $project): bool
    {
        $result = true;

        $projectId = $project->getId();

        $this->logger->info(sprintf('send project categories project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::EXT_ID, 0);
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::STATUS, ProjectProductInterface::STATUS_NEW);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectProductRepository->getList($searchCriteria);

        /** @var $projectProducts ProjectProductInterface[] */
        $projectProducts = $searchResult->getItems();

        foreach ($projectProducts as $projectProduct) {
            $isEntitySent = $this->sendEntity($project, $projectProduct);

            $result = $isEntitySent ? $result : false;
        }

        return $result;
    }

    private function sendEntity(ProjectInterface $project, ProjectProductInterface $projectProduct): bool
    {
        $result = true;

        // Skip already transferred products
        if ($projectProduct->getExtId() > 0) {
            return true;
        }

        $productId = $projectProduct->getEntityId();

        try {
            $product = $this->productRepository->get($productId);
        } catch (NoSuchEntityException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('product %s => %s', $productId, $message));

            return false;
        }

        $itemRequest = $this->itemPostMapper->map($product, $project);

        try {
            $response = $this->itemApi->post($itemRequest);

            // save project_product ext_id
            $extId = $response->getId();
            $projectProduct->setExtId($extId);
            $projectProduct->setStatus(ProjectProductInterface::STATUS_EXPORTED);

            $this->projectProductRepository->save($projectProduct);

            $this->logger->info(sprintf('product %s, ext-id:%s => success', $productId, $extId));
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('product %s => %s', $productId, $message));
            $result = false;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('product %s => %s', $productId, $message));
            $result = false;
        }

        return $result;
    }
}
