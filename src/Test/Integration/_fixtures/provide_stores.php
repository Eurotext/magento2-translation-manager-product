<?php
declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var $store \Magento\Store\Model\Store */
$store = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Store\Model\Store::class);
$store->isObjectNew(true);
$store->setName('test_store');
$store->setCode('test_store');
$store->setIsActive(1);
$store->setWebsiteId(1);
$store->save();

return $store;
