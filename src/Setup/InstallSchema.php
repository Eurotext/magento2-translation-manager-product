<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Setup;

use Eurotext\TranslationManagerProduct\Setup\Service\CreateProjectProductSchema;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
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
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createProjectProductSchema->execute($setup);
    }
}
