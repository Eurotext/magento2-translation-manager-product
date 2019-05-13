<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Ui\EntityDataLoader;

use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerProduct\Test\Integration\Provider\ProjectProductProvider;
use Eurotext\TranslationManagerProduct\Ui\EntityDataLoader\ProductEntityDataLoader;

class ProductEntityDataLoaderTest extends IntegrationTestAbstract
{
    /** @var ProductEntityDataLoader */
    private $sut;

    /** @var ProjectProductProvider */
    private $projectProductProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->sut = $this->objectManager->get(ProductEntityDataLoader::class);

        $this->projectProvider        = $this->objectManager->get(ProjectProvider::class);
        $this->projectProductProvider = $this->objectManager->get(ProjectProductProvider::class);
    }

    /**
     * @magentoDataFixture loadFixture
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldReturnProjectProductsArray()
    {
        $project = $this->projectProvider->createProject('some-name');

        $projectId = $project->getId();

        $projectProduct = $this->projectProductProvider->createProjectProduct($projectId, 10);

        $data = [];

        $result = $this->sut->load($projectId, $data);

        $this->assertTrue($result);
        $this->assertArrayHasKey('products', $data);

        $products = $data['products'];

        $this->assertArrayHasKey(0, $products);

        $entityData = $products[0];

        $this->assertArrayHasKey('id', $entityData);
        $this->assertEquals($projectProduct->getId(), $entityData['id']);
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../../_fixtures/provide_products.php';
    }

}