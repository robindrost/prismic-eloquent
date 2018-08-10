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
        'uid',
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
        $this->api = ! empty($api) ? $api : resolve(Api::class);
    }

    /**
     * @inheritdoc
     */
    public function single(): ModelContract
    {
        return $this->model->attachDocument(
            $this->api->getSingle($this->model::getTypeName(), $this->options)
        )->resolveDocuments();
    }

    /**
     * @inheritdoc
     */
    public function find(string $uid): ModelContract
    {
        return $this->model->attachDocument(
            $this->api->getByUID($this->model::getTypeName(), $uid, $this->options)
        )->resolveDocuments();
    }

    /**
     * @inheritdoc
     */
    public function findById(string $id): ModelContract
    {
        return $this->model->attachDocument(
            $this->api->getByID($id, $this->options)
        )->resolveDocuments();
    }

    /**
     * @inheritdoc
     */
    public function findByIds(array $ids): Collection
    {
        $models = array_map(function ($document) {
            return (clone $this->model)->attachDocument($document)->resolveDocuments();
        }, $this->api()->getByIDs($ids)->results);

        return $this->model::newCollection($models);
    }

    /**
     * @inheritdoc
     */
    public function first() : ModelContract
    {
        return $this->all()->first();
    }

    /**
     * @inheritdoc
     */
    public function all(): Collection
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
            return (clone $this->model)->attachDocument($result)->resolveDocuments();
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
    ): LengthAwarePaginatorContract {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $this->addOption('pageSize', $perPage);
        $query = $this->pagerQuery($page);
        $results = $query->results;

        $items = $this->model::newCollection(array_map(function ($result) {
            return (clone $this->model)->attachDocument($result)->resolveDocuments();
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
    public function whereType(String $type): QueryBuilderContract
    {
        $this->addPredicate('type', $type);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereTag(String $tag): QueryBuilderContract
    {
        $this->addPredicate('tags', [$tag], 'at');
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereTags(array $tags): QueryBuilderContract
    {
        $this->addPredicate('tags', $tags);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function where(string $field, $value): QueryBuilderContract
    {
        $this->addPredicate($field, $value);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereIn(string $field, array $values): QueryBuilderContract
    {
        $this->addPredicate($field, $values);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereNot(string $field, $value): QueryBuilderContract
    {
        $this->addPredicate($field, $value, 'not');
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereLanguage($language) : QueryBuilderContract
    {
        $this->addOption('lang', $language);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function search($text) : QueryBuilderContract
    {
        $this->addPredicate('document', $text, 'fulltext');
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fetch(...$fields): QueryBuilderContract
    {
        $this->addOption('fetchLinks', implode(',', $fields));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function select(...$fields): QueryBuilderContract
    {
        $this->addOption('fetch', implode(',', array_map(function ($field) {
            return "{$this->model::getTypeName()}.{$field}";
        }, $fields)));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orderBy(string $field, $sort = 'desc'): QueryBuilderContract
    {
        if (! in_array($field, self::DOCUMENT_ATTRIBUTES)) {
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
    public function addPredicate(string $field, $value, $method = null)
    {
        if (! in_array($field, self::DOCUMENT_ATTRIBUTES)) {
            $field = "my.{$this->model::getTypeName()}.{$field}";
        } else {
            $field = "document.{$field}";
        }

        if (! empty($method)) {
            $predicate = Predicates::{$method}($field, $value);
        } elseif (is_array($value)) {
            $predicate = Predicates::any($field, $value);
        } else {
            $predicate = Predicates::at($field, $value);
        }

        $this->predicates[] = $predicate;
    }

    /**
     * @inheritdoc
     */
    public function addOption(string $option, $value)
    {
        $this->options[$option] = $value;
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
}
