<?php

namespace App\Models\Other;

use Illuminate\Support\Collection;

class Paginated
{
    /**
     * @var Collection
     */
    protected $items;

    /**
     * @var int
     */
    protected $pages;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $total;

    /**
     * Paginated constructor.
     *
     * @param Collection $items
     * @param $total
     * @param $pages
     * @param $page
     * @param $limit
     */
    public function __construct(Collection $items, $total, $pages, $page, $limit)
    {
        $this->items = $items;
        $this->total = $total;
        $this->pages = $pages;
        $this->page = $page;
        $this->limit = $limit;
    }

    /**
     * Get limit for page use.
     *
     * @return int
     */
    public function limit()
    {
        return $this->limit;
    }

    /**
     * Get items for current page.
     *
     * @return Collection
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Number of pages.
     *
     * @return int
     */
    public function pages()
    {
        return $this->pages;
    }

    /**
     * Current page.
     *
     * @return int
     */
    public function page()
    {
        return $this->page;
    }

    /**
     * Get total number of items.
     *
     * @return int
     */
    public function total()
    {
        return $this->total;
    }
}
