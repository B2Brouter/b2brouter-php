<?php

namespace B2BRouter;

/**
 * Paginated collection of resources.
 *
 * @implements \Iterator<int, array>
 */
class Collection implements \Iterator, \Countable
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var array|null
     */
    private $meta;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param array $data
     * @param array|null $meta
     */
    public function __construct(array $data, $meta = null)
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->position = 0;
    }

    /**
     * Get all items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get the metadata.
     *
     * @return array|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Get the total count from metadata.
     *
     * @return int|null
     */
    public function getTotal()
    {
        return isset($this->meta['total']) ? $this->meta['total'] : null;
    }

    /**
     * Get the offset from metadata.
     *
     * @return int|null
     */
    public function getOffset()
    {
        return isset($this->meta['offset']) ? $this->meta['offset'] : null;
    }

    /**
     * Get the limit from metadata.
     *
     * @return int|null
     */
    public function getLimit()
    {
        return isset($this->meta['limit']) ? $this->meta['limit'] : null;
    }

    /**
     * Check if there are more results.
     *
     * @return bool
     */
    public function hasMore()
    {
        $total = $this->getTotal();
        $offset = $this->getOffset();
        $limit = $this->getLimit();

        if ($total === null || $offset === null || $limit === null) {
            return false;
        }

        return ($offset + $limit) < $total;
    }

    // Iterator implementation

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->data[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->position;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    // Countable implementation

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->data);
    }
}
