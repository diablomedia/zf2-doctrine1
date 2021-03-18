<?php declare(strict_types=1);

namespace Doctrine1\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Doctrine_Collection;
use Doctrine_Connection_Exception;
use Doctrine_Hydrator_Exception;
use Doctrine_Pager;
use Doctrine_Pager_Exception;
use Doctrine_Query_Exception;
use Doctrine_Record;

class Pager implements AdapterInterface
{
    /**
     * @var Doctrine_Pager
     */
    protected $pager;

    /**
     * @var array<string, Doctrine_Collection>
     */
    protected $results = [];

    /**
     * @var int|null
     */
    protected $numResults;

    public function __construct(Doctrine_Pager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * @return Doctrine_Collection<Doctrine_Record>
     * @throws Doctrine_Pager_Exception
     * @throws Doctrine_Query_Exception
     * @throws Doctrine_Connection_Exception
     * @throws Doctrine_Hydrator_Exception
     */
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
            // The executeCount method requires the diablomedia fork of doctrine1
            // https://github.com/diablomedia/doctrine1/
            // Which will prevent double execution of the pager's query
            if (method_exists($this->pager, 'executeCount')) {
                $this->pager->executeCount();
            } else {
                $this->pager->execute();
            }
            $this->numResults = $this->pager->getNumResults();
        }

        return $this->numResults;
    }
}
