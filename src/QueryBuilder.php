<?php

namespace RobinDrost\PrismicEloquent;

use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Prismic\Api;
use Prismic\Predicates;
use RobinDrost\PrismicEloquent\Contracts\Model as ModelContract;
use RobinDrost\PrismicEloquent\Contracts\QueryBuilder as QueryBuilderContract;

class QueryBuilder implements QueryBuilderContract
{

    /**
     * @const array
     */
    protected const DOCUMENT_ATTRIBUTES = [
        'id',
        'type',
        'href',
        'tags',
        'first_publication_date',
        'last_publication_date',
        'linked_documents',
        'lang',
        'alternate_languages',
        'data',
    ];

    /**
     * @var ModelContract
     */
    protected $model;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var array
     */
    protected $predicates = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $toResolve = [];

    /**
     * @param ModelContract $model
     * @param Api|null $api
     */
    public function __construct(ModelContract $model, Api $api = null)
    {
        $this->model = $model;
        $this->api = !empty($api) ? $api : resolve(Api::class);
    }

    /**
     * @inheritdoc
     */
    public function single() : ? ModelContract
    {
        return $this->model->attachDocument(
            $this->api->getSingle($this->model::getTypeName(), $this->options)
        )->resolveRelationships();
    }

    /**
     * @inheritdoc
     */
    public function find(string $uid) : ? ModelContract
    {
        return $this->where('uid', $uid)->first();
    }

    /**
     * @inheritdoc
     */
    public function findById(string $id) : ? ModelContract
    {
        return $this->where('id', $id)->first();
    }

    /**
     * @inheritdoc
     */
    public function findByIds(array $ids) : Collection
    {
        return $this->whereIn('id', $ids)->all();
    }

    /**
     * @inheritdoc
     */
    public function first() : ? ModelContract
    {
        return $this->all()->first();
    }

    /**
     * @inheritdoc
     */
    public function all() : Collection
    {
        $query = $this->pagerQuery();
        $results = $query->results;

        if ($query->total_results_size > $query->results_size) {
            $pagesAmount = round($query->total_results_size / $query->results_size);

            foreach (range(2, $pagesAmount) as $number) {
                $results = array_merge($results, $this->pagerQuery(intval($number)));
            }
        }

        $models = array_map(function ($result) {
            return (clone $this->model)->attachDocument($result)->resolveRelationships();
        }, $results);

        return $this->model::newCollection($models);
    }

    /**
     * @inheritdoc
     */
    public function paginate(
        int $perPage = 10,
        array $fields = [],
        string $pageName = 'page',
        $page = null
    ) : LengthAwarePaginatorContract {
        $page = $page ? : Paginator::resolveCurrentPage($pageName);

        $this->addOption('pageSize', $perPage);
        $query = $this->pagerQuery($page);
        $results = $query->results;

        $items = $this->model::newCollection(array_map(function ($result) {
            return (clone $this->model)->attachDocument($result)->resolveRelationships();
        }, $results));

        return Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items' => $items,
            'total' => $query->total_results_size,
            'perPage' => $perPage,
            'currentPage' => $page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function whereType(String $type) : QueryBuilderContract
    {
        return $this->addPredicate('type', $type);
    }

    /**
     * @inheritdoc
     */
    public function whereTag(String $tag) : QueryBuilderContract
    {
        return $this->addPredicate('tags', [$tag], 'at');
    }

    /**
     * @inheritdoc
     */
    public function whereTags(array $tags) : QueryBuilderContract
    {
        return $this->addPredicate('tags', $tags);
    }

    /**
     * @inheritdoc
     */
    public function where(string $field, $value) : QueryBuilderContract
    {
        return $this->addPredicate($field, $value);
    }

    /**
     * @inheritdoc
     */
    public function whereIn(string $field, array $values) : QueryBuilderContract
    {
        return $this->addPredicate($field, $values);
    }

    /**
     * @inheritdoc
     */
    public function whereNot(string $field, $value) : QueryBuilderContract
    {
        return $this->addPredicate($field, $value, 'not');
    }

    /**
     * @inheritdoc
     */
    public function whereLanguage($language) : QueryBuilderContract
    {
        return $this->addOption('lang', $language);
    }

    /**
     * @inheritdoc
     */
    public function search($text) : QueryBuilderContract
    {
        return $this->addPredicate('document', $text, 'fulltext');
    }

    /**
     * @inheritdoc
     */
    public function fetch(...$fields) : QueryBuilderContract
    {
        return $this->addOption('fetchLinks', implode(',', $fields));
    }

    /**
     * @inheritdoc
     */
    public function select(...$fields) : QueryBuilderContract
    {
        return $this->addOption('fetch', implode(',', array_map(function ($field) {
            return "{$this->model::getTypeName()}.{$field}";
        }, $fields)));
    }

    /**
     * @inheritdoc
     */
    public function orderBy(string $field, $sort = 'desc') : QueryBuilderContract
    {
        if (!in_array($field, self::DOCUMENT_ATTRIBUTES)) {
            $field = "my.{$this->model::getTypeName()}.{$field}";
        } else {
            $field = "document.{$field}";
        }

        $this->addOption('orderings', "[{$field}]");

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addPredicate(string $field, $value, $method = null) : QueryBuilderContract
    {
        if (!in_array($field, self::DOCUMENT_ATTRIBUTES)) {
            $field = "my.{$this->model::getTypeName()}.{$field}";
        } else {
            $field = "document.{$field}";
        }

        if (!empty($method)) {
            $predicate = Predicates::{$method}($field, $value);
        } elseif (is_array($value)) {
            $predicate = Predicates::any($field, $value);
        } else {
            $predicate = Predicates::at($field, $value);
        }

        $this->predicates[] = $predicate;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addOption(string $option, $value) : QueryBuilderContract
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Get the Prismic Api object.
     *
     * @return Api
     */
    public function api() : Api
    {
        return $this->api;
    }

    /**
     * Run a pager query.
     *
     * @param int $page
     * @return mixed
     */
    protected function pagerQuery($page = 1)
    {
        return $this->api()->query(
            $this->predicates,
            array_merge($this->options, ['page' => $page])
        );
    }

    /**
     * Implements the static __call method that will check if the give call is
     * a scope method on the model.
     *
     * @param string $method
     * @param mixed $arguments
     *
     * @return QueryBuilderContract
     */
    public function __call($method, $arguments)
    {
        $scope = 'scope' . ucfirst($method);

        if (method_exists($this->model, $scope)) {
            $this->model->{$scope}($this, ...$arguments);
            return $this;
        }

        return $this->{$method}(...$arguments);
    }
}
