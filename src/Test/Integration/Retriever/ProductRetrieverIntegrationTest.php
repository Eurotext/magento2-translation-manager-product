<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\RestApiClient\Request\ProjectTranslateRequest;
use Eurotext\TranslationManager\Service\Project\CreateProjectServiceInterface;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerProduct\Model\ProjectProduct;
use Eurotext\TranslationManagerProduct\Retriever\ProductRetriever;
use Eurotext\TranslationManagerProduct\Repository\ProjectProductRepository;
use Eurotext\TranslationManagerProduct\Sender\ProductSender;
use Eurotext\TranslationManagerProduct\Test\Integration\Provider\ProjectProductProvider;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class ProductRetrieverIntegrationTest extends IntegrationTestAbstract
{
    /** @var ProjectProductRepository */
    private $projectProductRepository;

    /** @var ProductRetriever */
    private $sut;

    /** @var ProjectProductProvider */
    private $projectProductProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var ProductSender */
    private $productSender;

    /** @var CreateProjectServiceInterface */
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
            ProductRetriever::class,
            [
                'itemApi' => $itemApi,
            ]
        );

        $this->projectApi = new ProjectV1Api($config);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class,
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
    public function testItShouldRetrieveProjectProducts()
    {
        $productId = 10;
        $name      = __CLASS__ . '-product-retriever';

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
        $this->projectApi->translate(new ProjectTranslateRequest($project->getExtId()));

        try {
            // Set The area code otherwise image resizing will fail
            /** @var State $appState */
            $appState = $this->objectManager->get(State::class);
            $appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
        }

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

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
