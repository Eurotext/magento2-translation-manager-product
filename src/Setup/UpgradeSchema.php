<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Setup;

use Eurotext\TranslationManagerProduct\Setup\Service\CreateProjectProductSchema;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * UpdateSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var CreateProjectProductSchema
     */
    private $createProjectProductSchema;

    public function __construct(
        CreateProjectProductSchema $createProjectProductSchema
    ) {
        $this->createProjectProductSchema = $createProjectProductSchema;
    }

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createProjectProductSchema->execute($setup);
    }
}
