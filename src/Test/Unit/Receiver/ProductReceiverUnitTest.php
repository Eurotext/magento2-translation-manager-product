<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Receiver;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Receiver\ProductReceiver;
use Eurotext\TranslationManagerProduct\Repository\ProjectProductRepository;
use Eurotext\TranslationManagerProduct\ScopeConfig\ProductScopeConfigReader;
use Eurotext\TranslationManagerProduct\Test\Builder\ProjectProductMockBuilder;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\TransferException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;

class ProductReceiverUnitTest extends UnitTestAbstract
{
    /** @var ProductReceiver */
    private $sut;

    /** @var ProjectMockBuilder */
    private $projectMockBuilder;

    /** @var ProjectProductMockBuilder */
    private $projectProductMockBuilder;

    /** @var ProjectProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $projectProductRepository;

    /** @var ProductScopeConfigReader|\PHPUnit_Framework_MockObject_MockObject */
    private $productItemGetMapper;

    /** @var SearchResultsInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $searchResults;

    /** @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $productRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ItemV1ApiInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    protected function setUp()
    {
        parent::setUp();

        $this->itemApi = $this->getMockBuilder(ItemV1ApiInterface::class)->getMock();

        $this->projectProductRepository =
            $this->getMockBuilder(ProjectProductRepositoryInterface::class)->getMock();

        $this->searchCriteriaBuilder =
            $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
                 ->setMethods(['create', 'addFilter'])->getMock();
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->searchResults = $this->getMockBuilder(SearchResultsInterface::class)->getMockForAbstractClass();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)->getMock();

        $this->projectMockBuilder        = new ProjectMockBuilder($this);
        $this->projectProductMockBuilder = new ProjectProductMockBuilder($this);

        $this->sut = $this->objectManager->getObject(
            ProductReceiver::class,
            [
                'itemApi'                  => $this->itemApi,
                'projectProductRepository' => $this->projectProductRepository,
                'productRepository'        => $this->productRepository,
                'searchCriteriaBuilder'    => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldReceiveProjectProducts()
    {
        $productId = 1;
        $storeId   = 3;
        $status    = ProjectProductInterface::STATUS_IMPORTED;
        $lastError = '';

        $project = $this->projectMockBuilder->buildProjectMock();
        $project->method('getStoreviewDst')->willReturn($storeId);

        $projectProduct = $this->projectProductMockBuilder->buildProjectProductMock();
        $projectProduct->expects($this->once())->method('setStatus')->with($status);
        $projectProduct->expects($this->once())->method('setLastError')->with($lastError);
        $projectProduct->expects($this->once())->method('getProductId')->willReturn($productId);
        $projectProduct->expects($this->once())->method('getExtId')->willReturn(2423);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectProductRepository->expects($this->once())->method('save')->with($projectProduct);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectProduct]);

        $product = $this->getMockBuilder(ProductInterface::class)->getMock();
        $this->productRepository->expects($this->once())->method('getById')
                                ->with($productId, true, $storeId)->willReturn($product);

        // Receive Project from Eurotext
        $result = $this->sut->receive($project);

        $this->assertTrue($result);
    }

    public function testItShouldSetLastErrorForGuzzleException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new TransferException($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    public function testItShouldSetLastErrorForException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new \Exception($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    private function runTestExceptionsAreHandledCorrectly(\Exception $apiException)
    {
        $status    = ProjectProductInterface::STATUS_ERROR;

        $project = $this->projectMockBuilder->buildProjectMock();

        $projectProduct = $this->projectProductMockBuilder->buildProjectProductMock();
        $projectProduct->expects($this->once())->method('setStatus')->with($status);
        $projectProduct->expects($this->once())->method('setLastError')->with($apiException->getMessage());

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectProductRepository->expects($this->once())->method('save')->with($projectProduct);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectProduct]);

        $product = $this->getMockBuilder(ProductInterface::class)->getMock();
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);

        $this->itemApi->method('get')->willThrowException($apiException);

        // Receive Project from Eurotext
        $result = $this->sut->receive($project);

        $this->assertFalse($result);
    }
}
