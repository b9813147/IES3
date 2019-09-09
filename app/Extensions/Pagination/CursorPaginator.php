<?php

namespace App\Extensions\Pagination;

use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Support\Str;

/**
 * Class CursorPaginator
 *
 * @package App\Extensions\Pagination
 */
class CursorPaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, PaginatorContract
{
    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    protected $hasMore;

    /**
     * @var Cursor
     */
    protected $cursor = null;

    /**
     * @var string
     */
    protected $identifier = 'id';

    /**
     * Should cast to date the identifier.
     *
     * @var bool
     */
    protected $dateIdentifier = false;

    /**
     * @var string
     */
    protected $prevPageName = 'prev_id';

    /**
     * @var string
     */
    protected $nextPageName = 'next_id';

    /**
     * Create a new paginator instance.
     *
     * @param string $identifier
     * @param mixed $items
     * @param int $perPage
     * @param array $options (path, query, fragment, pageName)
     *
     * @return void
     */
    public function __construct($identifier, $items, $perPage, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->cursor = $this->resolveCurrentCursor();
        $this->identifier = $identifier;
        $this->perPage = $perPage;
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;

        $this->setItems($items);
    }

    /**
     * Set the items for the paginator.
     *
     * @param mixed $items
     *
     * @return void
     */
    protected function setItems($items)
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);

        $this->hasMore = $this->items->count() > $this->perPage;

        $this->items = $this->items->slice(0, $this->perPage);
    }

    /**
     * Resolve the current cursor.
     *
     * @param Request|null $request
     *
     * @return Cursor
     */
    public function resolveCurrentCursor(Request $request = null)
    {
        $request = $request ?? request();
        $req_prev = $request->input($this->prevPageName);
        $req_next = $request->input($this->nextPageName);
        return new Cursor($req_prev, $req_next);
    }

    /**
     * The URL for the next page, or null.
     *
     * @return string|null
     */
    public function nextPageUrl()
    {
        if ($nextCursor = $this->nextCursor()) {
            return $this->url([
                $this->nextPageName => $nextCursor
            ]);
        }

        return null;
    }

    /**
     * Get next cursor.
     *
     * @return mixed
     */
    public function nextCursor()
    {
        if ($this->hasMorePages()) {
            return $this->lastItem();
        }

        return null;
    }

    /**
     * Get the URL for the previous page.
     *
     * @return null|string
     */
    public function previousPageUrl()
    {
        if ($prevCursor = $this->prevCursor()) {
            return $this->url([
                $this->prevPageName => $prevCursor
            ]);
        }

        return null;
    }

    /**
     * Get previous cursor.
     *
     * @return int|mixed
     */
    public function prevCursor()
    {
        if ($this->isFirstPage()) {
            return ($this->cursor->getPrev() && $this->isEmpty()) ?
                $this->cursor->getPrev() :
                $this->firstItem();
        }

        return null;
    }

    /**
     * Determine if the page is first.
     *
     * @return bool
     */
    public function isFirstPage()
    {
        return !$this->cursor->getNext();
    }

    /**
     * Get the URL for a given page.
     *
     * @param array $page
     *
     * @return string
     */
    public function url($page)
    {
        $parameters = $page;

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path
            . (Str::contains($this->path, '?') ? '&' : '?')
            . http_build_query($parameters, '', '&')
            . $this->buildFragment();
    }

    /**
     * Return the first identifier of the results.
     *
     * @return int
     */
    public function firstItem()
    {
        return count($this->items) > 0 ? $this->getIdentifier($this->items->first()) : null;
    }

    /**
     * Return the last identifier of the results.
     *
     * @return mixed
     */
    public function lastItem()
    {
        return count($this->items) > 0 ? $this->getIdentifier($this->items->last()) : null;
    }

    /**
     * Gets and casts identifier.
     *
     * @param $model
     *
     * @return mixed
     */
    protected function getIdentifier($model)
    {
        if (!isset($model)) {
            return null;
        }
        $id = $model->{$this->identifier};

        if (!$this->dateIdentifier) {
            return $id;
        }

        return (is_string($id)) ? $id : $id->timestamp;
    }

    /**
     * Render the paginator using a given view.
     *
     * @param string|null $view
     * @param array $data
     *
     * @return string
     */
    public function render($view = null, $data = [])
    {
        // No render method
        return '';
    }

    /**
     * Determine if there is more items in the data store.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->hasMore;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->items->toArray(),
            'prev_page_url' => $this->previousPageUrl(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path,
            'per_page' => $this->perPage(),
            $this->prevPageName => $this->prevCursor(),
            $this->nextPageName => $this->nextCursor(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}