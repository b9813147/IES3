<?php

namespace App\Extensions\Pagination;

class Cursor
{
    /**
     * Previous cursor value.
     *
     * @var mixed
     */
    protected $prev;

    /**
     * Next cursor value.
     *
     * @var mixed
     */
    protected $next;

    /**
     * Cursor constructor.
     *
     * @param mixed $prev
     * @param mixed $next
     */
    public function __construct($prev = null, $next = null)
    {
        $this->prev = $prev;
        $this->next = $next;
    }

    /**
     * Get the prev cursor value.
     *
     * @return mixed
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * Set the prev cursor value.
     *
     * @param int $prev
     *
     * @return Cursor
     */
    public function setPrev($prev)
    {
        $this->prev = $prev;

        return $this;
    }

    /**
     * Get the next cursor value.
     *
     * @return mixed
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Set the next cursor value.
     *
     * @param int $next
     *
     * @return Cursor
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
    }
}