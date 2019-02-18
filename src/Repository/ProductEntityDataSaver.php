<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntityDataSaverInterface;
use Eurotext\TranslationManagerProduct\Seeder\ProductSeeder;
use Eurotext\TranslationManagerProduct\Service\CleanProductsService;

class ProductEntityDataSaver implements EntityDataSaverInterface
{
    /**
     * @var ProductSeeder
     */
    private $entitySeeder;

    /**
     * @var CleanProductsService
     */
    private $cleanProductsService;

    public function __construct(
        ProductSeeder $entitySeeder,
        CleanProductsService $cleanProductsService
    ) {
        $this->entitySeeder         = $entitySeeder;
        $this->cleanProductsService = $cleanProductsService;
    }

    public function save(ProjectInterface $project, array &$data): bool
    {
        if (!array_key_exists('products', $data)) {
            throw new \InvalidArgumentException('entities data not found');
        }

        $entities = $data['products'];

        if (count($entities) === 0) {
            throw new \InvalidArgumentException('entities not found');
        }

        $skus      = [];
        $entityIds = [];

        foreach ($entities as $entity) {
            if (array_key_exists('id', $entity)) {
                $entityIds[] = $entity['id'];
            }
            $skus[] = $entity['sku'];
        }

        // delete removed products first, so we do not need to calculate after the new entities are seeded
        $deleted = $this->cleanProductsService->cleanMissingByIds($project, $entityIds);

        // Seed selected skus
        $seed = $this->entitySeeder->seed($project, $skus);

        return $seed === true;
    }
}