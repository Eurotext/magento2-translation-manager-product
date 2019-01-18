<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Seeder;

use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Model\ProjectProductFactory;
use Eurotext\TranslationManagerProduct\Seeder\ProductSeeder;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Psr\Log\LoggerInterface;

class ProductSeederUnitTest extends UnitTestAbstract
{
    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ProjectProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectProductRepository;

    /** @var ProjectProductFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $projectProductFactory;

    /** @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $productRepository;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var ProjectMockBuilder */
    private $projectBuilder;

    /** @var ProductSeeder */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->projectBuilder = new ProjectMockBuilder($this);

        $this->productRepository        = $this->createMock(ProductRepositoryInterface::class);
        $this->projectProductFactory    = $this->createMock(ProjectProductFactory::class);
        $this->projectProductRepository = $this->createMock(ProjectProductRepositoryInterface::class);
        $this->searchCriteriaBuilder    = $this->createMock(SearchCriteriaBuilder::class);
        $this->logger                   = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            ProductSeeder::class, [
                'productRepository'        => $this->productRepository,
                'projectProductFactory'    => $this->projectProductFactory,
                'projectProductRepository' => $this->projectProductRepository,
                'searchCriteriaBuilder'    => $this->searchCriteriaBuilder,
                'logger'                   => $this->logger,
            ]
        );
    }

    public function testItShouldSeedProducts()
    {
        $projectId    = 33;
        $totalCount   = 1;
        $entityId     = 11;
        $pEntityCount = 0;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::ENTITY_ID, $entityId],
            [ProjectProductSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Product
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->once())->method('getId')->willReturn($entityId);

        $productResult = $this->createMock(ProductSearchResultsInterface::class);
        $productResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $productResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->productRepository->expects($this->once())->method('getList')->willReturn($productResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->once())->method('save');

        // New project entity
        $pProduct = $this->createMock(ProjectProductInterface::class);
        $pProduct->expects($this->once())->method('setProjectId')->with($projectId);
        $pProduct->expects($this->once())->method('setEntityId')->with($entityId);
        $pProduct->expects($this->once())->method('setStatus')->with(ProjectProductInterface::STATUS_NEW);

        $this->projectProductFactory->expects($this->once())->method('create')->willReturn($pProduct);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldSkipSeedingIfProductIsSeededAlready()
    {
        $projectId    = 33;
        $totalCount   = 1;
        $entityId     = 11;
        $pEntityCount = 1;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::ENTITY_ID, $entityId],
            [ProjectProductSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Product
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->once())->method('getId')->willReturn($entityId);

        $productResult = $this->createMock(ProductSearchResultsInterface::class);
        $productResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $productResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->productRepository->expects($this->once())->method('getList')->willReturn($productResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->never())->method('save');

        $this->projectProductFactory->expects($this->never())->method('create');

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionsWhileSaving()
    {
        $projectId    = 33;
        $totalCount   = 1;
        $entityId     = 11;
        $pEntityCount = 0;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::ENTITY_ID, $entityId],
            [ProjectProductSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Product
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->once())->method('getId')->willReturn($entityId);

        $productResult = $this->createMock(ProductSearchResultsInterface::class);
        $productResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $productResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->productRepository->expects($this->once())->method('getList')->willReturn($productResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->once())->method('save')->willThrowException(new \Exception);

        // New project entity
        $pProduct = $this->createMock(ProjectProductInterface::class);
        $pProduct->expects($this->once())->method('setProjectId')->with($projectId);
        $pProduct->expects($this->once())->method('setEntityId')->with($entityId);
        $pProduct->expects($this->once())->method('setStatus')->with(ProjectProductInterface::STATUS_NEW);

        $this->projectProductFactory->expects($this->once())->method('create')->willReturn($pProduct);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertFalse($result);
    }

    public function testItShouldSkipSeedingIfNoProductsAreFound()
    {
        $entityTotalCount = 0;

        $searchCriteria = new SearchCriteria();

        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(ProductSearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($entityTotalCount);
        $searchResult->expects($this->never())->method('getItems');

        $this->productRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldAddEntitiesFilter()
    {
        $entity           = 'some-entity';
        $entities         = [$entity];
        $entityTotalCount = 0;

        $searchCriteria = new SearchCriteria();

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
                                    ->withConsecutive(['sku', $entities, 'in'])->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(ProductSearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($entityTotalCount);
        $searchResult->expects($this->never())->method('getItems');

        $this->productRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project, $entities);

        $this->assertTrue($result);
    }

    public function testItShouldLogEntitiesNotFound()
    {
        $projectId    = 33;
        $totalCount   = 1;
        $entityId     = 11;
        $pEntityCount = 0;
        $entity       = 'some-entity';
        $entities     = [$entity, 'not-found-entity'];

        $this->searchCriteriaBuilder->method('addFilter')
                                    ->withConsecutive(
                                        ['sku', $entities, 'in'],
                                        [ProjectProductSchema::ENTITY_ID, $entityId],
                                        [ProjectProductSchema::PROJECT_ID, $projectId]
                                    )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Product
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->once())->method('getId')->willReturn($entityId);
        $product->expects($this->once())->method('getSku')->willReturn($entity);

        $productResult = $this->createMock(ProductSearchResultsInterface::class);
        $productResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $productResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->productRepository->expects($this->once())->method('getList')->willReturn($productResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->once())->method('save');

        // New project entity
        $pProduct = $this->createMock(ProjectProductInterface::class);
        $pProduct->expects($this->once())->method('setProjectId')->with($projectId);
        $pProduct->expects($this->once())->method('setEntityId')->with($entityId);
        $pProduct->expects($this->once())->method('setStatus')->with(ProjectProductInterface::STATUS_NEW);

        $this->projectProductFactory->expects($this->once())->method('create')->willReturn($pProduct);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        $this->logger->expects($this->once())->method('error');

        // TEST
        $result = $this->sut->seed($project, $entities);

        $this->assertTrue($result);
    }

}