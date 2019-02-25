<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerProduct\Repository\ProductEntityDataSaver;
use Eurotext\TranslationManagerProduct\Seeder\ProductSeeder;
use Eurotext\TranslationManagerProduct\Service\CleanProductsService;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use PHPUnit\Framework\MockObject\MockObject;

class ProductEntityDataSaverTest extends UnitTestAbstract
{
    /** @var ProductEntityDataSaver */
    private $sut;

    /** @var CleanProductsService|MockObject */
    private $cleanProducts;

    /** @var ProductSeeder|MockObject */
    private $entitySeeder;

    protected function setUp()
    {
        parent::setUp();

        $this->entitySeeder  = $this->createMock(ProductSeeder::class);
        $this->cleanProducts = $this->createMock(CleanProductsService::class);

        $this->sut = $this->objectManager->getObject(
            ProductEntityDataSaver::class, [
                'entitySeeder'         => $this->entitySeeder,
                'cleanProductsService' => $this->cleanProducts,
            ]
        );
    }

    public function testItShouldSeedProducts()
    {
        $productId  = 4;
        $productSku = 'some-sku';

        $data = [
            'products' => [
                $productId => [
                    'id'  => $productId,
                    'sku' => $productSku,
                ],
            ],
        ];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $this->cleanProducts->expects($this->once())->method('cleanMissingByIds')
                            ->with($project, [0 => $productId])->willReturn(true);

        $this->entitySeeder->expects($this->once())->method('seed')
                           ->with($project, [0 => $productSku])->willReturn(true);

        $save = $this->sut->save($project, $data);

        $this->assertTrue($save);
    }

    public function testItShouldThrowExceptionIfProductsAreMissing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = [];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $this->sut->save($project, $data);
    }

    public function testItShouldThrowExceptionIfProductsAreEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = [
            'products' => [],
        ];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $this->sut->save($project, $data);
    }

}