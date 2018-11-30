<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerProduct\Model;

use Eurotext\TranslationManager\Model\AbstractProjectEntity;
use Eurotext\TranslationManagerProduct\Model\ResourceModel\ProjectProductCollection;
use Eurotext\TranslationManagerProduct\Model\ResourceModel\ProjectProductResource;

class ProjectProduct extends AbstractProjectEntity
{
    const CACHE_TAG = 'eurotext_project_product';

    protected function _construct()
    {
        $this->_init(ProjectProductResource::class);
        $this->_setResourceModel(ProjectProductResource::class, ProjectProductCollection::class);
    }

    protected function getCacheTag(): string
    {
        return self::CACHE_TAG;
    }
}
