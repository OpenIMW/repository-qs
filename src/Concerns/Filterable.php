<?php

namespace IMW\RepositoryQS\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Filterable
{
    /**
     * Apply filter.
     *
     * @return self
     */
    protected function applyFilters()
    {
        foreach ($this->filters as $requestkey => $filter) {
            // If no key was set we'll asume in the same table
            if (is_int($requestkey)) {
                $requestkey = $filter;
            }

            // if the request does'nt contain the filter we'll continue
            if (!request()->has($requestkey)) {
                continue;
            }

            // if this not a field it's likely to be a relation
            // we'll guess it and apply the query
            if (method_exists($this->model, $filter)) {
                $this->filterByRelationship($filter, request()->get($requestkey));
            }

            // if not we'll assume this a simple field let's just use where()
            else {
                $this->query->where($filter, request()->get($requestkey));
            }

            // if the loop excution is here
        }

        return $this;
    }

    /**
     * Guess relationship and apply query.
     *
     * @param string $relation
     * @param mixed  $filterValue
     *
     * @return void
     */
    protected function filterByRelationship($relationAccessor, $filterValue)
    {
        $relation = $this->model->$relationAccessor();
        switch (class_basename(get_class($relation))) {
            case 'BelongsToMany':
                $this->applyBelongsToManyFilter($relation, $filterValue);
                break;

            case 'BelongsTo':
                $this->applyBelongsToFilter($relation, $filterValue);
                break;

                // TO DO: add all available relationships
            default:
                // code...
                break;
        }
    }

    /**
     * Apply BelongsToMany join query.
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @param mixed                                                 $filterValue
     *
     * @return void
     */
    protected function applyBelongsToManyFilter(BelongsToMany $relation, $filterValue)
    {
        $this->query->join(
            $relation->getTable(),
            $relation->getQualifiedForeignPivotKeyName(),
            '=',
            $this->model->getQualifiedKeyName()
        )->where($relation->getQualifiedRelatedPivotKeyName(), $filterValue);
    }

    /**
     * Apply BelongsToMany join query.
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsTo $relation
     * @param mixed                                             $filterValue
     *
     * @return void
     */
    protected function applyBelongsToFilter(BelongsTo $relation, $filterValue)
    {
        // $this->query->where($relation)
        // $this->query->join(
        //     $relation->getTable(),
        //     $relation->getQualifiedKeyName(),
        //     '=',
        //     $this->model->getQualifiedKeyName()
        // )->where($relation->getQualifiedRelatedPivotKeyName(), $filterValue);
    }
}
