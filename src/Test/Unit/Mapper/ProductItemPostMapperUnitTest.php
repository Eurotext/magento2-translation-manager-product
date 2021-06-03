<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Mapper;

use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Eurotext\TranslationManagerProduct\Mapper\ProductItemPostMapper;
use Eurotext\TranslationManagerProduct\ScopeConfig\ProductScopeConfigReader;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\AttributeInterface;

class ProductItemPostMapperUnitTest extends UnitTestAbstract
{
    /** @var ProductScopeConfigReader|\PHPUnit_Framework_MockObject_MockObject */
    private $productScopeConfig;

    /** @var ScopeConfigReaderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeConfig;

    /** @var ProductItemPostMapper */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scopeConfig        = $this->createMock(ScopeConfigReaderInterface::class);
        $this->productScopeConfig = $this->createMock(ProductScopeConfigReader::class);

        $this->sut = $this->objectManager->getObject(
            ProductItemPostMapper::class,
            [
                'scopeConfigReader'  => $this->scopeConfig,
                'productScopeConfig' => $this->productScopeConfig,
            ]
        );
    }

    public function testMap()
    {
        $projectId = 123;
        $productId = 32123;

        $storeViewSrc = 1;
        $storeViewDst = 2;
        $langSrc      = 'de_DE';
        $langDst      = 'en_US';

        $nameValue      = 'product-name';
        $descValue      = 'This is some description';
        $shortDescValue = 'really short description';

        $attrCodeName      = 'name';
        $attrCodeDesc      = 'description';
        $attrCodeShortDesc = 'short_description';
        $attributesEnabled = [$attrCodeName, $attrCodeDesc, $attrCodeShortDesc];

        // Mock Product with Custom Attributes
        $descAttribute = $this->createMock(AttributeInterface::class);
        $descAttribute->expects($this->once())->method('getValue')->willReturn($descValue);

        $shortDescAttribute = $this->createMock(AttributeInterface::class);
        $shortDescAttribute->expects($this->once())->method('getValue')->willReturn($shortDescValue);

        /** @var ProductInterface|\PHPUnit_Framework_MockObject_MockObject $product */
        $product = $this->createMock(ProductInterface::class);
        $product->expects($this->any())->method('getId')->willReturn($productId);
        $product->expects($this->once())->method('getName')->willReturn($nameValue);
        $product->expects($this->exactly(3))
                ->method('getCustomAttribute')
                ->willReturnOnConsecutiveCalls(null, $descAttribute, $shortDescAttribute);

        // Mock Project
        /** @var ProjectInterface|\PHPUnit_Framework_MockObject_MockObject $project */
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getExtId')->willReturn($projectId);
        $project->expects($this->once())->method('getStoreviewSrc')->willReturn($storeViewSrc);
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeViewDst);

        // Mock ScopeConfig
        $this->scopeConfig->expects($this->exactly(2))
                          ->method('getLocaleForStore')
                          ->willReturnOnConsecutiveCalls($langSrc, $langDst);

        // Mock ProductScopeConfig
        $this->productScopeConfig->expects($this->once())
                                 ->method('getAttributesEnabled')->willReturn($attributesEnabled);

        // Execute test
        $request = $this->sut->map($product, $project);

        // ASSERT
        $this->assertInstanceOf(ItemPostRequest::class, $request);

        $this->assertEquals($projectId, $request->getProjectId());
        $this->assertEquals($langSrc, $request->getSource());
        $this->assertEquals($langDst, $request->getTarget());
        $this->assertEquals(ProductItemPostMapper::ENTITY_TYPE, $request->getTextType());

        $itemData = $request->getData();

        $meta = $itemData->getMeta();
        $this->assertEquals($productId, $meta['item_id']);
        $this->assertEquals($productId, $meta['entity_id']);
        $this->assertEquals(ProductItemPostMapper::ENTITY_TYPE, $meta['entity_type']);

        $data = $itemData->getData();
        $this->assertEquals($nameValue, $data[$attrCodeName]);
        $this->assertEquals($descValue, $data[$attrCodeDesc]);
        $this->assertEquals($shortDescValue, $data[$attrCodeShortDesc]);
    }
}
