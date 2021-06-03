<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\RestApiClient\Response\Project\ItemPostResponse;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Mapper\ProductItemPostMapper;
use Eurotext\TranslationManagerProduct\Sender\ProductSender;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\RequestException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class ProductSenderUnitTest extends UnitTestAbstract
{
    /** @var ProductSender */
    private $sut;

    /** @var ProjectProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectProductRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $productRepository;

    /** @var ProductItemPostMapper|\PHPUnit_Framework_MockObject_MockObject */
    private $productItemPostMapper;

    /** @var ItemV1Api|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1Api::class);

        $this->projectProductRepository = $this->createMock(ProjectProductRepositoryInterface::class);

        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);

        $this->productItemPostMapper = $this->createMock(ProductItemPostMapper::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            ProductSender::class,
            [
                'itemApi' => $this->itemApi,
                'projectProductRepository' => $this->projectProductRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'productRepository' => $this->productRepository,
                'itemPostMapper' => $this->productItemPostMapper,
                'logger' => $this->logger,
            ]
        );

    }

    public function testItShouldSendProjectEntities()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $productId = 11;

        $projectEntity = $this->createMock(ProjectProductInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($productId);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectProductInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->expects($this->once())->method('getById')
                                 ->with($productId)->willReturn($product);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->productItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectProductRepository->expects($this->once())->method('save')->with($projectEntity);

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldNoSendIfEntityHasExtId()
    {
        $extIdSaved = 12345;

        $projectEntity = $this->createMock(ProjectProductInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->productRepository->expects($this->never())->method('getById');
        $this->productItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectProductRepository->expects($this->never())->method('save');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionIfProductIsNotFound()
    {
        $extIdSaved = 0;
        $productId = 11;

        $projectEntity = $this->createMock(ProjectProductInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($productId);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->productRepository->expects($this->once())->method('getById')
                                 ->with($productId)
                                 ->willThrowException(new NoSuchEntityException());

        $this->productItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectProductRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionFromTheApi()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $productId = 23;

        $projectEntity = $this->createMock(ProjectProductInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($productId);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->expects($this->once())->method('getById')
                                 ->with($productId)
                                 ->willReturn($product);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->productItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->never())->method('getId');
        $exception = $this->createMock(RequestException::class);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willThrowException($exception);

        $this->projectProductRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionWhileSavingTheProduct()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $productId = 222;

        $projectEntity = $this->createMock(ProjectProductInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($productId);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectProductInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->expects($this->once())->method('getById')
                                 ->with($productId)
                                 ->willReturn($product);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->productItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectProductRepository->expects($this->once())->method('save')
                                        ->with($projectEntity)->willThrowException(new \Exception());

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }
}
