<?php

namespace Doctrine1\Paginator\Adapter;

use Zend\Paginator\Adapter\AdapterInterface;
use Doctrine_Collection;

class Collection implements AdapterInterface
{
    protected $collection;

    public function __construct(Doctrine_Collection $collection)
    {
        $this->collection = $collection;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        $a = array();

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
