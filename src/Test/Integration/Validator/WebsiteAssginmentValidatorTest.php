<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Seeder;

use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManagerProduct\Validator\WebsiteAssignmentValidator;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

class WebsiteAssginmentValidatorTest extends IntegrationTestAbstract
{
    /** @var StoreRepositoryInterface|MockObject */
    private $storeRepository;

    /** @var ProjectMockBuilder */
    private $projectBuilder;

    /** @var WebsiteAssignmentValidator|MockObject */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->projectBuilder = new ProjectMockBuilder($this);

        $this->storeRepository = $this->createMock(StoreRepositoryInterface::class);

        $this->sut = new WebsiteAssignmentValidator($this->storeRepository);
    }

    public function testItShouldValidateWebsiteAssignment()
    {
        $storeId   = 44;
        $websiteId = 234;
        $entityId  = 11;
        $entity    = 'some-entity';

        // Store
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);

        $this->storeRepository->expects($this->once())->method('getById')->with($storeId)->willReturn($store);

        // Product
        $productExtension = $this->objectManager->get(ProductExtensionInterface::class);
        $productExtension->expects($this->once())->method('getWebsiteIds')->willReturn([$websiteId]);

        /** @var ProductInterface|MockObject $product */
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($entity);
        $product->expects($this->once())->method('getExtensionAttributes')->willReturn($productExtension);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeId);

        // TEST
        $result = $this->sut->validate($project, $product);

        $this->assertTrue($result);
    }

    /**
     * @param int[] $productWebsiteIds
     *
     * @dataProvider provideProductWebsiteAssignments
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testItShouldValidateMissingWebsiteAndLog($productWebsiteIds)
    {
        $this->expectException(\InvalidArgumentException::class);

        $storeId   = 44;
        $websiteId = 234;
        $entityId  = 11;
        $entity    = 'some-entity';

        // Store
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);

        $this->storeRepository->expects($this->once())->method('getById')->with($storeId)->willReturn($store);

        // Product
        $productExtension = $this->objectManager->get(ProductExtensionInterface::class);
        $productExtension->expects($this->once())->method('getWebsiteIds')->willReturn($productWebsiteIds);

        /** @var ProductInterface|MockObject $product */
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($entity);
        $product->expects($this->once())->method('getExtensionAttributes')->willReturn($productExtension);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeId);

        // TEST
        $result = $this->sut->validate($project, $product);

        $this->assertTrue($result);
    }

    public function provideProductWebsiteAssignments(): array
    {
        return [
            'missing-assignment' => [
                'productWebsiteIds' => [55],
            ],
            'no-websites'        => [
                'productWebsiteIds' => [],
            ],
            'no-websites-array'  => [
                'productWebsiteIds' => null,
            ],
        ];
    }

    public function testItShouldThrowExceptionIfProductExtensionIsMissing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $storeId   = 44;
        $websiteId = 234;
        $entityId  = 11;
        $entity    = 'some-entity';

        // Store
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);

        $this->storeRepository->expects($this->once())->method('getById')->with($storeId)->willReturn($store);

        // Product
        /** @var ProductInterface|MockObject $product */
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($entity);
        $product->expects($this->once())->method('getExtensionAttributes')->willReturn(null);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeId);

        // TEST
        $result = $this->sut->validate($project, $product);

        $this->assertTrue($result);
    }

}
