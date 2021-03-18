<?php declare(strict_types=1);

namespace Doctrine1\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Doctrine_Collection;
use Doctrine_Record;
use Doctrine_Record_Exception;

class Collection implements AdapterInterface
{
    /**
     * @var Doctrine_Collection<Doctrine_Record>
     */
    protected $collection;

    /**
     * @param Doctrine_Collection<Doctrine_Record> $collection
     */
    public function __construct(Doctrine_Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return array<int, Doctrine_Record>
     * @throws Doctrine_Record_Exception
     */
    public function getItems($offset, $itemCountPerPage): array
    {
        $a = [];

        if ($offset < $this->count()) {
            for ($i = 0, $count = $this->count(); $i < $itemCountPerPage && $offset < $count; $i++, $offset++) {
                $a[] = $this->collection->get($offset);
            }
        }

        return $a;
    }

    public function count()
    {
        return $this->collection->count();
    }
}
