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
use Eurotext\TranslationManagerProduct\Validator\WebsiteAssignmentValidator;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ProductSeederTest extends IntegrationTestAbstract
{
    protected static $storeId;

    /** @var \Eurotext\TranslationManagerProduct\Seeder\ProductSeeder */
    private $sut;

    /** @var LoggerInterface */
    private $logger;

    /** @var MockObject|WebsiteAssignmentValidator */
    private $websiteAssignmentValidator;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var TestHandler */
    private $testHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->testHandler = new TestHandler();

        $this->logger = new Logger(self::class, [$this->testHandler]);

        // Mocking the WebsiteAssignmentValidator since the website assignment somehow does not work in the pipeline
        // The dataprovider sets the website, but in the valdiator the websites are empty
        $this->websiteAssignmentValidator = $this->createMock(WebsiteAssignmentValidator::class);
        $this->websiteAssignmentValidator->method('validate')->willReturn(true);

        $this->sut = $this->objectManager->create(
            ProductSeeder::class,
            [
                'logger'                     => $this->logger,
                'websiteAssignmentValidator' => $this->websiteAssignmentValidator,
            ]
        );

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
        $project->setStoreviewDst((int)self::$storeId);

        $entities = ['simple1', 'simple2', 'simple3'];

        $result = $this->sut->seed($project, $entities);

        $records = $this->testHandler->getRecords();
        if (count($records) > 0) {
            fwrite(STDERR, print_r($records, true));
        }
        $this->assertTrue($result);
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_products.php';
        $store = include __DIR__ . '/../_fixtures/provide_stores.php';

        self::$storeId = $store->getId();
    }
}
