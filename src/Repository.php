<?php

namespace IMW\RepositoryQS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use IMW\RepositoryQS\Contracts\Repository as RepositoryContract;
use InvalidArgumentException;

abstract class Repository implements RepositoryContract
{
    /**
     * The Eloquent model to interact with.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Query to get the repository collection.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    public $query;

    /**
     * The relations to eager load.
     *
     * @return array
     */
    public $with = [];

    /**
     * Relationship models count.
     *
     * @var array
     */
    public $withCount = [];

    /**
     * Default local applied scopes.
     *
     * @var array
     */
    public $scopes = [];

    /**
     * Determine if we should remove global scopes.
     *
     * @var array|bool
     */
    public $withoutGlobalScopes = false;

    /**
     * Add additional global scopes.
     *
     * @var array
     */
    public $globalScopes = [];

    /**
     * The field for default sorting.
     *
     * @var string
     */
    public $sortField = null;

    /**
     * The default sort type.
     *
     * @var string
     */
    public $sortType = 'asc';

    /**
     * Allowed fields for sorting.
     *
     * @return array
     */
    public $sortable = [];

    /**
     * Allowed fields for search.
     *
     * @return array
     */
    public $searchable = [];

    /**
     * Allowed relations for filter.
     *
     * @return array
     */
    public $filters = [];

    /**
     * Determine if we should force pagination even if not requested.
     *
     * @var bool
     */
    public $forcePagination = false;

    /**
     * The total of records per one requests.
     *
     * @var int
     */
    public $recordsPerPage = 12;

    /**
     * Class constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $model
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function __construct($model = null)
    {
        // Initialize the model
        $this->init($model);

        // Initialize the query builder
        $this->newQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function list()
    {
        // Apply global scope if any
        if ($this->hasGlobalScopes()) {
            $this->applyGlobalScopes();
        }
        // Apply sort if the repository is sortable
        if ($this->isSortable()) {
            $this->applySort();
        }

        // Apply search query if searchable
        if ($this->isSearchable()) {
            $this->applySearch();
        }

        // Apply filters if any
        if ($this->isFilterable()) {
            $this->applyFilters();
        }

        // Apply local scope if has any
        if ($this->hasScopes()) {
            $this->applyScopes();
        }

        // Eager load relations if any
        if ($this->shouldLoadRelations()) {
            $this->loadRelations();
        }

        // Eager load relations count if any
        if ($this->shouldLoadCounts()) {
            $this->loadCounts();
        }

        // Use pagination if this is a paginable repository
        if ($this->isPaginable()) {
            return $this->paginate();
        }

        return $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function put(array $data)
    {
        foreach ($data as $attribute => $value) {
            $this->model->{$attribute} = $value;
        }

        $this->model->save();

        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    public function show($record)
    {
        // Get the record if not automatically resolved by the router
        if (!$record instanceof \Illuminate\Database\Eloquent\Model) {
            $record = $this->query
                ->where($this->model->getRouteKeyName(), $record)
                ->firstOrFail();
        }

        // Load any requested counts
        if ($this->shouldLoadCounts()) {
            $record->loadCount($this->withCount);
        }

        // Load any requested relations
        if ($this->shouldLoadRelations()) {
            $record->loadMissing($this->with);
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function update($record, array $data)
    {
        // Get the record if not automatically resolved by the router
        if (!$record instanceof \Illuminate\Database\Eloquent\Model) {
            $record = $this->query
                ->where($this->model->getRouteKeyName(), $record)
                ->firstOrFail();
        }

        // Assign $data attribute to this record
        foreach ($data as $attribute => $value) {
            $record->{$attribute} = $value;
        }

        $record->save();

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($record)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function forceDestroy($record)
    {
        //
    }

    /**
     * Forwarding calls to the query builder.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->query, $method], $args);
    }

    /**
     * Initialize the repository with a Model.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $model
     *
     * @return void
     */
    public function init($model)
    {
        // Rely on the model passed to the constructor
        // Than fallback to model property on the repository if exists
        $model = $model ?? $this->model;

        if ($model) {
            $this->model = app()->make($model);
        }

        // If no model was set we'll guess it based on repository name
        // if the developer decide to enable auto repositories guessing
        elseif (config('repository.auto', false) && $model = $this->guessModel()) {
            $this->model = $model;
        }

        // If we can't set model to interact with we'll throw an exception
        // with the repository class name, to help developer even more
        else {
            throw new InvalidArgumentException(
                class_basename(get_class($this)).' need a Model to interact with.'
            );
        }
    }

    /**
     * Instaniate new query builder for the model.
     *
     * @return self
     */
    public function newQueryBuilder()
    {
        if (is_array($this->withoutGlobalScopes) && !empty($this->withoutGlobalScopes)) {
            $this->query = $this->model
                ->newQuery()
                ->withoutGlobalScopes($this->withoutGlobalScopes);
        } elseif ($this->withoutGlobalScopes) {
            $this->query = $this->model->newQueryWithoutScopes();
        } else {
            $this->query = $this->model->newQuery();
        }

        return $this;
    }

    /**
     * Determine if this repository need local scopes.
     *
     * @return bool
     */
    protected function hasScopes()
    {
        return (isset($this->scopes) && is_array($this->scopes) && !empty($this->scopes))
            ? true
            : false;
    }

    /**
     * Apply local scopes.
     *
     * @return self
     */
    protected function applyScopes()
    {
        foreach ($this->scopes as $scope) {
            $this->query = $this->query->$scope();
        }

        return $this;
    }

    /**
     * Determine if this repository need local scopes.
     *
     * @return bool
     */
    protected function hasGlobalScopes()
    {
        return (isset($this->globalScopes) && is_array($this->globalScopes) && !empty($this->globalScopes))
            ? true
            : false;
    }

    /**
     * Apply global scopes.
     *
     * @return self
     */
    protected function applyGlobalScopes()
    {
        foreach ($this->globalScopes as $identifier => $scope) {
            $this->query = $this->query->withGlobalScope($identifier, new $scope());
        }

        return $this;
    }

    /**
     * Apply filter.
     *
     * @return self
     */
    // protected function applyFilters()
    // {
    //     foreach ($this->filters as $key => $filter) {
    //         # code...
    //     }

    //     return $this;
    // }

    /**
     * Determine whether this repository should load any relation.
     *
     * @return bool
     */
    protected function shouldLoadRelations()
    {
        return (isset($this->with) && is_array($this->with) && !empty($this->with))
            ? true
            : false;
    }

    /**
     * Eager load relations.
     *
     * @return self
     */
    protected function loadRelations()
    {
        $this->query = $this->query->with($this->with);

        return $this;
    }

    /**
     * Determine whether the repository should eager load relations count.
     *
     * @return bool
     */
    protected function shouldLoadCounts()
    {
        return (isset($this->withCount) && is_array($this->withCount) && !empty($this->withCount))
            ? true
            : false;
    }

    /**
     * Eager load relations count.
     *
     * @return self
     */
    protected function loadCounts()
    {
        $this->query = $this->query->withCount($this->withCount);

        return $this;
    }

    /**
     * Check whether this repository is sortable.
     *
     * @return bool
     */
    protected function isSortable()
    {
        foreach (class_uses($this) as $trait) {
            if (Str::endsWith($trait, 'Sortable')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether this repository is sortable.
     *
     * @return bool
     */
    protected function isFilterable()
    {
        foreach (class_uses($this) as $trait) {
            if (Str::endsWith($trait, 'Filterable')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether this repository need to paginate.
     *
     * @return bool
     */
    protected function isPaginable()
    {
        // If pagination is not forced and per_page is absent from the request
        // We'll ignore pagination, usefull for search queries
        // if you want to force pagination set `forcePagination`=true
        if (!$this->forcePagination && !request()->has('per_page')) {
            return false;
        }

        foreach (class_uses($this) as $trait) {
            if (Str::endsWith($trait, 'Paginable')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether this repository need to use search filter.
     *
     * @return bool
     */
    protected function isSearchable()
    {
        foreach (class_uses($this) as $trait) {
            if (Str::endsWith($trait, 'Searchable')) {
                return true;
            }
        }

        return false;
    }
}
