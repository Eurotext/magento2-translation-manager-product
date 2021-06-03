<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Service;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Service\CleanProductsService;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Psr\Log\LoggerInterface;

class CleanProductsServiceTest extends UnitTestAbstract
{
    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ProjectProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectProductRepository;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var CleanProductsService */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectProductRepository = $this->createMock(ProjectProductRepositoryInterface::class);
        $this->searchCriteriaBuilder    = $this->createMock(SearchCriteriaBuilder::class);
        $this->logger                   = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            CleanProductsService::class, [
                'projectProductRepository' => $this->projectProductRepository,
                'searchCriteriaBuilder'    => $this->searchCriteriaBuilder,
                'logger'                   => $this->logger,
            ]
        );
    }

    public function testItShouldDeleteProducts()
    {
        $projectId   = 33;
        $entityCount = 1;
        $entityIds   = [1, 2, 3];

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::PROJECT_ID, $projectId],
            [ProjectProductSchema::ID, $entityIds, 'nin']
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')
                                    ->willReturn(new SearchCriteria());

        // New project entity
        $product = $this->createMock(ProjectProductInterface::class);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($entityCount);
        $projectEntityResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->once())->method('delete')->with($product);

        // project mock
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getId')->willReturn($projectId);
        /** @var ProjectInterface $project */

        // TEST
        $result = $this->sut->cleanMissingByIds($project, $entityIds);

        $this->assertTrue($result);
    }

    public function testItShouldSkipDeletionWhenCountIsZero()
    {
        $projectId   = 33;
        $entityId    = 11;
        $entityCount = 0;
        $entityIds   = [1, 2, 3];

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::PROJECT_ID, $projectId],
            [ProjectProductSchema::ID, $entityIds, 'nin']
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')
                                    ->willReturn(new SearchCriteria());

        // New project entity
        $product = $this->createMock(ProjectProductInterface::class);
        $product->method('getId')->willReturn($entityId);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($entityCount);
        $projectEntityResult->expects($this->never())->method('getItems');

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->never())->method('delete');

        // project mock
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getId')->willReturn($projectId);
        /** @var ProjectInterface $project */

        // TEST
        $result = $this->sut->cleanMissingByIds($project, $entityIds);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionsDuringDeletion()
    {
        $projectId   = 33;
        $entityCount = 1;
        $entityIds   = [1, 2, 3];

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectProductSchema::PROJECT_ID, $projectId],
            [ProjectProductSchema::ID, $entityIds, 'nin']
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')
                                    ->willReturn(new SearchCriteria());

        // New project entity
        $product = $this->createMock(ProjectProductInterface::class);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($entityCount);
        $projectEntityResult->expects($this->once())->method('getItems')->willReturn([$product]);

        $this->projectProductRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectProductRepository->expects($this->once())->method('delete')->with($product)
                                       ->willThrowException(new \Exception('EXCEPTION !!!!!'));

        $this->logger->expects($this->once())->method('error');

        // project mock
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getId')->willReturn($projectId);
        /** @var ProjectInterface $project */

        // TEST
        $result = $this->sut->cleanMissingByIds($project, $entityIds);

        $this->assertTrue($result);
    }
}
