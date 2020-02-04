<?php

namespace IMW\RepositoryQS\Concerns;

use Illuminate\Support\Str;

trait Sortable
{
    /**
     * Sort the repository.
     *
     * @return self
     */
    protected function applySort()
    {
        // Check if the sort use a related model
        if (Str::contains($this->getSortField(), '.')) {
            $this->applyRelationSort();
        } else {
            $this->query = $this->query->orderBy($this->getSortField(), $this->getSortType());
        }

        return $this;
    }

    private function applyRelationSort()
    {
        list($relation, $field) = explode('.', $this->getSortField());

        // Check if the sorting field belong to translations
        if ($relation == 'translations') {
            $this->query = $this->query->orderByTranslation($field, $this->getSortType());
        }

        return $this;
    }

    /**
     * Get the field used for sorting.
     *
     * @return string
     */
    private function getSortField()
    {
        $sortField = request()->get('sort_by', $this->sortField);

        foreach ($this->sortable as $field) {
            if ($field == $sortField) {
                return $field;
            } elseif (Str::contains('.', $field)) {
                if (Str::endsWith($field, ".{$sortField}")) {
                    return $field;
                }
            }
        }

        return $this->sortField;
    }

    /**
     * Get the sort type (asc, desc).
     *
     * @return string
     */
    private function getSortType()
    {
        $sortType = request()->get('sort_type', $this->sortType);

        return in_array($sortType, ['asc', 'desc'])
            ? $sortType
            : $this->sortType;
    }
}
