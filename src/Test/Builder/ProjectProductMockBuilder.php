<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Builder;

use Eurotext\TranslationManagerProduct\Api\Data\ProjectProductInterface;
use PHPUnit\Framework\TestCase;

class ProjectProductMockBuilder
{
    /**
     * @var \PHPUnit\Framework\TestCase
     */
    private $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function buildProjectProductMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ProjectProductInterface::class)->getMock();
    }

    protected function getMockBuilder($className): \PHPUnit_Framework_MockObject_MockBuilder
    {
        return new \PHPUnit_Framework_MockObject_MockBuilder($this->testCase, $className);
    }
}
