<?php

namespace IMW\RepositoryQS\Concerns;

/**
 * Paginate records.
 */
trait Paginable
{
    /**
     * Paginate the collection result.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate()
    {
        return $this->query
                    ->paginate($this->getRecordsPerPage())
                    ->appends(request()->query());
    }

    /**
     * Simple Pagination for the result.
     *
     * @return \Illuminate\Pagination\Paginator
     */
    protected function simplePaginate()
    {
        return $this->query
                    ->simplePaginate($this->getRecordsPerPage())
                    ->appends(request()->query());
    }

    /**
     * Get total records per page.
     *
     * @return int
     */
    protected function getRecordsPerPage()
    {
        // Let's validate if value is pure integer
        return request()->has('per_page') && is_numeric(request()->get('per_page'))
            ? (int) request()->get('per_page')
            : $this->recordsPerPage;
    }
}
