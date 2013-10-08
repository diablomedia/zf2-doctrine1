<?php

namespace Doctrine1\Paginator\Adapter;

use Zend\Paginator\Adapter\AdapterInterface;

class Pager implements AdapterInterface
{
    protected $pager;

    protected $results = array();
    protected $numResults;

    public function __construct(\Doctrine_Pager $pager)
    {
        $this->pager = $pager;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        // Caching results for the offset/itemcount
        // in case this is called more than once by the paginator
        // for whatever reason
        $key = $offset . ':' . $itemCountPerPage;
        if (!isset($this->results[$key])) {
            $this->pager->setMaxPerPage($itemCountPerPage);
            $this->pager->setPage(($offset / $itemCountPerPage) + 1);

            $this->results[$key] = $this->pager->execute();
        }

        return $this->results[$key];
    }

    public function count()
    {
        if (is_null($this->numResults)) {
            $this->pager->executeCount();
            $this->numResults = $this->pager->getNumResults();
        }

        return $this->numResults;
    }
}
