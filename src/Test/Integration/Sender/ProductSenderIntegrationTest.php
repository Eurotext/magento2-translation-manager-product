<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Seeder;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerProduct\Repository\ProjectProductRepository;
use Eurotext\TranslationManagerProduct\Sender\ProductSender;
use Eurotext\TranslationManagerProduct\Test\Integration\Provider\ProjectProductProvider;

class ProductSenderIntegrationTest extends IntegrationTestAbstract
{
    /** @var ProjectProductRepository */
    private $projectProductRepository;

    /** @var ProductSender */
    private $sut;

    /** @var ProjectProductProvider */
    private $projectProductProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp()
    {
        parent::setUp();

        $configBuiler = new ConfigurationMockBuilder($this);
        $config       = $configBuiler->buildConfiguration();

        $itemApi = new ItemV1Api($config);

        $this->sut = $this->objectManager->create(
            ProductSender::class,
            [
                'itemApi' => $itemApi,
            ]
        );

        $this->projectProvider        = $this->objectManager->get(ProjectProvider::class);
        $this->projectProductProvider = $this->objectManager->get(ProjectProductProvider::class);

        $this->projectProductRepository = $this->objectManager->get(ProjectProductRepository::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testItShouldSendProjectProducts()
    {
        $productId = 10;
        $name      = __CLASS__ . '-product-sender';

        $project = $this->projectProvider->createProject($name);

        $projectProduct1  = $this->projectProductProvider->createProjectProduct($project->getId(), $productId);
        $projectProductId = $projectProduct1->getId();

        $result = $this->sut->send($project);

        $this->assertTrue($result);

        $projectProduct = $this->projectProductRepository->getById($projectProductId);

        $extId = $projectProduct->getExtId();

        $this->assertGreaterThan(0, $extId);

    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_products.php';
    }
}
