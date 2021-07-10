<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Eurotext\TranslationManagerProduct\Mapper\ProductItemGetMapper;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AttributeInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class ProductItemGetMapperUnitTest extends UnitTestAbstract
{
    /** @var ProductItemGetMapper */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->objectManager->getObject(ProductItemGetMapper::class);
    }

    public function testMap()
    {
        $name = 'some-name';

        $customAttribute = $this->createMock(AttributeInterface::class);
        $customAttribute->expects($this->once())->method('setValue')->with(null);

        /** @var MockObject|Product $product */
        $product = $this->getMockBuilder(Product::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['setUrlKey', 'getCustomAttribute'])
                        ->getMock();

        $product->expects($this->once())->method('setUrlKey')->with('');
        $product->expects($this->once())->method('getCustomAttribute')
                ->with('url_key')->willReturn($customAttribute);

        // Execute test
        $itemGetResponse = new ItemGetResponse();
        $itemGetResponse->setData(
            [
                '__meta' => [],
                'name' => $name,
            ]
        );

        $productResult = $this->sut->map($itemGetResponse, $product);

        // ASSERT
        $this->assertInstanceOf(Product::class, $productResult);
        $this->assertEquals($product, $productResult);
    }
}
