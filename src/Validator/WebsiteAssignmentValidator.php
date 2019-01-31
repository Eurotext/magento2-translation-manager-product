<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerProduct\Validator;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */
class WebsiteAssignmentValidator
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    public function __construct(StoreRepositoryInterface $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

    /**
     * @param ProjectInterface $project
     * @param ProductInterface $product
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \InvalidArgumentException
     */
    public function validate(ProjectInterface $project, ProductInterface $product): bool
    {
        /** @var $product ProductInterface */
        $productId        = (int)$product->getId();
        $productSku       = $product->getSku();
        $productExtension = $product->getExtensionAttributes();
        $storeviewDst     = $project->getStoreviewDst();

        // Load store to get website ID
        $store         = $this->storeRepository->getById($storeviewDst);
        $websiteIdDest = $store->getWebsiteId();

        if (!$productExtension instanceof ProductExtensionInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'product "%s"(%d) not assigned to website-id: %d (extension_attributes missing)',
                    $productSku, $productId, $websiteIdDest
                )
            );
        }

        $websiteIds = $productExtension->getWebsiteIds();
        if (!is_array($websiteIds)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'product "%s"(%d) not assigned to website-id: %d (no website assignments)',
                    $productSku, $productId, $websiteIdDest
                )
            );
        }

        if (!in_array($websiteIdDest, $websiteIds, false)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'product "%s"(%d) not assigned to website-id: %d',
                    $productSku, $productId, $websiteIdDest
                )
            );
        }

        return true;
    }
}