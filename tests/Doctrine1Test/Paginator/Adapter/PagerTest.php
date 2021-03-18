<?php declare(strict_types=1);

namespace Doctrine1Test\Paginator\Adapter;

use Doctrine1\Paginator\Adapter\Pager as PagerAdapter;
use PHPUnit\Framework\TestCase;
use Doctrine_Pager;

class PagerTest extends TestCase
{
    /**
     * @var PagerAdapter
     */
    protected $adapter;

    /**
     * @var Doctrine_Pager&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pager;

    /**
     * @var array<int, array<string, string>>
     */
    protected $values;

    public function setUp(): void
    {
        $values = [];

        for ($i = 1; $i <= 100; $i++) {
            $values[] = ['name' => 'Record ' . $i];
        }

        // Mock pager
        $this->pager = $this->getMockBuilder(Doctrine_Pager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute', 'setMaxPerPage', 'setPage', 'getNumResults'])
            ->getMock();

        $this->adapter = new PagerAdapter($this->pager);
        $this->values  = $values;
    }

    public function testCountOnlyGetsExecutedOnce(): void
    {
        $values = $this->values;

        $this->pager->expects($this->once())
            ->method('getNumResults')
            ->will($this->returnCallback(function () use ($values) {
                return count($values);
            }));

        $this->assertSame(count($values), $this->adapter->count());
        $this->assertSame(count($values), $this->adapter->count());
    }

    public function testCountHitsExecuteIfExecuteCountNotAvailable(): void
    {
        $values = $this->values;

        $this->pager->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->count();
    }

    public function testCountHitsExecuteCountIfAvailable(): void
    {
        $values = $this->values;

        // Have to rebuild the mock here so that the countExecute
        // method is available
        // Does phpunit offer a built-in way to do this?
        $pager = $this->getMockBuilder('Doctrine_Pager')
            ->disableOriginalConstructor()
            ->setMethods(['executeCount', 'execute', 'setMaxPerPage', 'setPage', 'getNumResults'])
            ->getMock();

        $pager->expects($this->once())
            ->method('executeCount')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $adapter = new PagerAdapter($pager);

        $adapter->count();
    }

    public function testGetItemsOnlyCallsExecuteOnceForSameOffsetCountCombo(): void
    {
        $values = $this->values;

        $this->pager->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(0, 5);
    }

    public function testGetItemsCallsExecuteIfOffsetChanges(): void
    {
        $values = $this->values;

        $this->pager->expects($this->exactly(2))
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(5, 5);
    }

    public function testGetItemsCallsExecuteIfPerPageChanges(): void
    {
        $values = $this->values;

        $this->pager->expects($this->exactly(2))
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(0, 10);
    }
}
