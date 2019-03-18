<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\ScopeConfig;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ProductScopeConfigReader
{
    const CONFIG_PATH_ATTRIBUTES_ENABLED = 'eurotext_translationmanager/product/attributes_enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getAttributesEnabled(): array
    {
        $data = (string)$this->scopeConfig->getValue(self::CONFIG_PATH_ATTRIBUTES_ENABLED);

        return explode(',', $data);
    }
}