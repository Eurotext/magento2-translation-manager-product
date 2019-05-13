<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Setup\Service;

use Eurotext\TranslationManager\Api\Setup\AddEntityTableColumnsInterface;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use Magento\Framework\Setup\SchemaSetupInterface;

class CreateProjectProductSchema
{
    /**
     * @var AddEntityTableColumnsInterface
     */
    private $addEntityTableColumns;

    public function __construct(AddEntityTableColumnsInterface $addEntityTableColumns)
    {
        $this->addEntityTableColumns = $addEntityTableColumns;
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $table = $connection->newTable($setup->getTable(ProjectProductSchema::TABLE_NAME));

        $this->addEntityTableColumns->execute($setup, $table);

        $connection->createTable($table);
    }
}
