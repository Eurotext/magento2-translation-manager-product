<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Seeder;

use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerProduct\Seeder\ProductSeeder;

class ProductSeederIntegrationTest extends IntegrationTestAbstract
{
    protected static $storeId;

    /** @var \Eurotext\TranslationManagerProduct\Seeder\ProductSeeder */
    protected $sut;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->sut = $this->objectManager->create(ProductSeeder::class);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldSeedProjectProducts()
    {
        $name = __CLASS__ . '-product-seeder';

        $project = $this->projectProvider->createProject($name);
        $project->setStoreviewSrc(1);
        $project->setStoreviewDst((int) self::$storeId);

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_products.php';
        $store = include __DIR__ . '/../_fixtures/provide_stores.php';

        self::$storeId = $store->getId();
    }
}
