<?php

namespace Doctrine1Test\Paginator\Adapter;

use Doctrine1\Paginator\Adapter\Pager as PagerAdapter;
use PHPUnit_Framework_TestCase;

class PagerTest extends PHPUnit_Framework_TestCase
{
    protected $adapter;
    protected $pager;
    protected $values;

    public function setUp()
    {
        $values = [];

        for ($i = 1; $i <= 100; $i++) {
            $values[] = ['name' => 'Record ' . $i];
        }

        // Mock pager
        $this->pager = $this->getMockBuilder('Doctrine_Pager')
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'setMaxPerPage', 'setPage', 'getNumResults'])
            ->getMock();

        $this->adapter = new PagerAdapter($this->pager);
        $this->values  = $values;
    }

    public function testCountOnlyGetsExecutedOnce()
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

    public function testCountHitsExecuteIfExecuteCountNotAvailable()
    {
        $values = $this->values;

        $this->pager->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->count();
    }

    public function testCountHitsExecuteCountIfAvailable()
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

    public function testGetItemsOnlyCallsExecuteOnceForSameOffsetCountCombo()
    {
        $values = $this->values;

        $this->pager->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(0, 5);
    }

    public function testGetItemsCallsExecuteIfOffsetChanges()
    {
        $values = $this->values;

        $this->pager->expects($this->exactly(2))
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(5, 5);
    }

    public function testGetItemsCallsExecuteIfPerPageChanges()
    {
        $values = $this->values;

        $this->pager->expects($this->exactly(2))
            ->method('execute')
            ->will($this->returnValue(array_slice($values, 0, 5)));

        $this->adapter->getItems(0, 5);
        $this->adapter->getItems(0, 10);
    }
}
