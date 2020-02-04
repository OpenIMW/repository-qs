<?php

namespace IMW\RepositoryQS\Concerns;

use Illuminate\Support\Str;

trait Searchable
{
    /**
     * Apply a search query.
     *
     * @return self
     */
    protected function applySearch()
    {
        if (request()->has('q')) {
            foreach ($this->searchable as $index => $field) {
                $method = $index == 0
                    ? 'where'
                    : 'orWhere';

                if (Str::contains($field, '.')) {
                    list($relation, $subField) = explode('.', $field, 2);
                    if ($relation == 'translations') {
                        $this->query = $this->query->orWhereTranslationLike($subField, $this->getSearchQuery());
                    }
                } else {
                    $this->query = $this->query->$method($field, 'like', $this->getSearchQuery());
                }
            }
        }

        return $this;
    }

    /**
     * Construct the query string.
     *
     * @return string
     */
    private function getSearchQuery()
    {
        return implode('', ['%', request()->get('q'), '%']);
    }
}
