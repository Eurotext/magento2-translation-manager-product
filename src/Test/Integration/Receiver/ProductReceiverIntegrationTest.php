<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Seeder;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\TranslationManager\Service\Project\CreateProjectService;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerProduct\Model\ProjectProduct;
use Eurotext\TranslationManagerProduct\Receiver\ProductReceiver;
use Eurotext\TranslationManagerProduct\Repository\ProjectProductRepository;
use Eurotext\TranslationManagerProduct\Sender\ProductSender;
use Eurotext\TranslationManagerProduct\Test\Integration\Provider\ProjectProductProvider;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class ProductReceiverIntegrationTest extends IntegrationTestAbstract
{
    /** @var ProjectProductRepository */
    private $projectProductRepository;

    /** @var ProductReceiver */
    private $sut;

    /** @var ProjectProductProvider */
    private $projectProductProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var ProductSender */
    private $productSender;

    /** @var CreateProjectService */
    private $createProject;

    /** @var ProjectV1Api */
    private $projectApi;

    protected function setUp()
    {
        parent::setUp();

        $configBuiler = new ConfigurationMockBuilder($this);
        $config       = $configBuiler->buildConfiguration();

        $itemApi = new ItemV1Api($config);

        $this->sut = $this->objectManager->create(
            ProductReceiver::class,
            [
                'itemApi' => $itemApi,
            ]
        );

        $this->projectApi = new ProjectV1Api($config);

        $this->createProject = $this->objectManager->create(
            CreateProjectService::class,
            ['projectApi' => $this->projectApi]
        );

        $this->productSender = $this->objectManager->create(
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testItShouldReceiveProjectProducts()
    {
        $productId = 10;
        $name      = __CLASS__ . '-product-receiver';

        $project = $this->projectProvider->createProject($name);

        $projectProduct1  = $this->projectProductProvider->createProjectProduct($project->getId(), $productId);
        $projectProductId = $projectProduct1->getId();

        // Create project at Eurotext
        $resultProjectCreate = $this->createProject->execute($project);
        $this->assertTrue($resultProjectCreate);

        // Send Project Products to Eurotext
        $resultSend = $this->productSender->send($project);
        $this->assertTrue($resultSend);

        // trigger translation progress
        $this->projectApi->translate($project->getExtId());

        try {
            // Set The area code otherwise image resizing will fail
            /** @var State $appState */
            $appState = $this->objectManager->get(State::class);
            $appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
        }

        // Receive Project from Eurotext
        $result = $this->sut->receive($project);

        $this->assertTrue($result);

        $projectProduct = $this->projectProductRepository->getById($projectProductId);
        $this->assertGreaterThan(0, $projectProduct->getExtId());
        $this->assertEquals(ProjectProduct::STATUS_IMPORTED, $projectProduct->getStatus());
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_products.php';
    }
}
