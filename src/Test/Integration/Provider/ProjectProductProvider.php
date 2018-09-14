<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Integration\Provider;

use Eurotext\TranslationManagerProduct\Model\ProjectProduct;
use Eurotext\TranslationManagerProduct\Repository\ProjectProductRepository;
use Magento\TestFramework\Helper\Bootstrap;

class ProjectProductProvider
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var $projectProductRepository ProjectProductRepository */
    private $projectProductRepository;

    public function __construct()
    {
        $this->objectManager            = Bootstrap::getObjectManager();
        $this->projectProductRepository = $this->objectManager->get(ProjectProductRepository::class);
    }

    /**
     * @param int $projectId
     * @param int $productId
     *
     * @return \Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface|\Eurotext\TranslationManagerProduct\Model\ProjectProduct
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createProjectProduct(int $projectId, int $productId)
    {
        /** @var ProjectProduct $object */
        $object = $this->objectManager->create(ProjectProduct::class);
        $object->setProjectId($projectId);
        $object->setProductId($productId);

        return $this->projectProductRepository->save($object);
    }
}
