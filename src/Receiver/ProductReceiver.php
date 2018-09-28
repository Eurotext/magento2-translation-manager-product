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
use Eurotext\TranslationManagerProduct\Mapper\ProductItemGetMapper;
use Eurotext\TranslationManagerProduct\Setup\EntitySchema\ProjectProductSchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @var ProductItemGetMapper
     */
    private $productItemGetMapper;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectProductRepositoryInterface $projectProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ProductItemGetMapper $productItemGetMapper,
        LoggerInterface $logger
    ) {
        $this->projectProductRepository = $projectProductRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->itemApi                  = $itemApi;
        $this->productRepository        = $productRepository;
        $this->productItemGetMapper     = $productItemGetMapper;
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
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::STATUS, ProjectProductInterface::STATUS_EXPORTED);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectProductRepository->getList($searchCriteria);

        $projectProducts = $searchResult->getItems();

        foreach ($projectProducts as $projectProduct) {
            $lastError = '';

            /** @var $projectProduct ProjectProductInterface */
            $itemExtId = $projectProduct->getExtId();
            $productId = $projectProduct->getProductId();

            try {
                $product = $this->productRepository->getById($productId, true, $storeId);

                $itemRequest = new ItemGetRequest($projectExtId, $itemExtId);

                $itemGetResponse = $this->itemApi->get($itemRequest);

                $this->productItemGetMapper->map($itemGetResponse, $product);

                $this->productRepository->save($product);

                $status = ProjectProductInterface::STATUS_IMPORTED;

                $this->logger->info(sprintf('product id:%d, ext-id:%d => success', $productId, $itemExtId));
            } catch (GuzzleException $e) {
                $status    = ProjectProductInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('product id:%d => %s', $productId, $lastError));
                $result = false;
            } catch (\Exception $e) {
                $status    = ProjectProductInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('product id:%d => %s', $productId, $lastError));
                $result = false;
            }

            $projectProduct->setStatus($status);
            $projectProduct->setLastError($lastError);
            $this->projectProductRepository->save($projectProduct);
        }

        return $result;
    }

}
