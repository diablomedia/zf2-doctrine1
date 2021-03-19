<?php declare(strict_types=1);

namespace Doctrine1Test\Paginator\Adapter;

use Doctrine1\Paginator\Adapter\Collection as CollectionAdapter;
use PHPUnit\Framework\TestCase;
use Doctrine_Collection;

class CollectionTest extends TestCase
{
    /**
     * @var CollectionAdapter
     */
    protected $adapter;

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

        // Mock collection
        $collection = $this->getMockBuilder(Doctrine_Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'count'])
            ->getMock();

        $collection->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($index) use ($values) {
                return $values[$index];
            }));

        $collection->expects($this->any())
            ->method('count')
            ->will($this->returnCallback(function () use ($values) {
                return count($values);
            }));

        $this->adapter = new CollectionAdapter($collection);
        $this->values  = $values;
    }

    public function testCountReturnsExpectedAmount(): void
    {
        $this->assertSame(count($this->values), $this->adapter->count());
    }

    public function testAdapterFetchesFirstSetOfItems(): void
    {
        $this->assertSame(
            [
                ['name' => 'Record 1'],
                ['name' => 'Record 2'],
                ['name' => 'Record 3'],
                ['name' => 'Record 4'],
                ['name' => 'Record 5'],
            ],
            $this->adapter->getItems(0, 5)
        );
    }

    public function testAdapterFetchesSecondSetOfItems(): void
    {
        $this->assertSame(
            [
                ['name' => 'Record 6'],
                ['name' => 'Record 7'],
                ['name' => 'Record 8'],
                ['name' => 'Record 9'],
                ['name' => 'Record 10'],
            ],
            $this->adapter->getItems(5, 5)
        );
    }

    public function testAdapterReturnsLastSetOfItemsLessThanPerPageCount(): void
    {
        $this->assertSame(
            [
                ['name' => 'Record 96'],
                ['name' => 'Record 97'],
                ['name' => 'Record 98'],
                ['name' => 'Record 99'],
                ['name' => 'Record 100'],
            ],
            $this->adapter->getItems(95, 30)
        );
    }
}
