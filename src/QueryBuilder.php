<?php

namespace RobinDrost\PrismicEloquent;

use Illuminate\Container\Container;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Prismic\Api;
use Prismic\Predicate;
use Prismic\Predicates;

class QueryBuilder
{

    /**
     * @var \Prismic\Api
     */
    protected $api;

    /**
     * @var Model
     */
    protected $model;

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
    protected $dateOptions = [
        'month',
        'year',
    ];

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->api = resolve(Api::class);

        $this->addOption('pageSize', 100);
        $this->addPredicate(Predicates::at('document.type', $this->model->getTypeName()));
    }

    /**
     * Retrieve a document that belongs to a single type. This method
     * does not work for repreatable types.
     *
     * @return Model
     */
    public function single()
    {
        $document = $this->api()->getSingle($this->model->getTypeName(), $this->options);
        $this->model->attachDocument($document);

        return $this->model;
    }

    /**
     * Get a single item from Prismic by id.
     *
     * @param  string $id
     * @return Model
     */
    public function findById($id)
    {
        $document = $this->api()->getByID($id, $this->options);
        $this->model->attachDocument($document);

        return $this->model;
    }

    /**
     * Get a single item from Prismic by uid.
     *
     * @param  string $uid
     * @return Model
     */
    public function find($uid)
    {
        $document = $this->api()->getByUid($this->model->getTypeName(), $uid, $this->options);
        $this->model->attachDocument($document);

        return $this->model;
    }

    /**
     * Return all the documents of the current model type.
     *
     * @param array $fields
     * @return Collection
     */
    public function all(array $fields = [])
    {
        return $this->get($fields);
    }

    /**
     * Get all the results based on the current predicates and options.
     *
     * @param array $fields
     * @return Model|Collection
     */
    public function get(array $fields = [])
    {
        $this->specifyFields($fields);

        $query = $this->pagerQuery();
        $results = $query->results;

        if ($query->total_results_size > $query->results_size) {
            $pagesAmount = round($query->total_results_size / $query->results_size);

            foreach (range(2, $pagesAmount) as $number) {
                $results = array_merge($results, $this->pagerQuery(intval($number)));
            }
        }

        $models = array_map(function ($result) {
            return $this->model::newInstance($result);
        }, $results);

        return $this->model->newCollection($models);
    }

    /**
     * Perform a paginate request. This method works the same as the
     * default eloquent model one.
     *
     * @param int $perPage
     * @param array $fields
     * @param string $pageName
     * @param null $page
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 10, array $fields = [], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: $this->model->getPerPage();

        $this->specifyFields($fields);

        $this->addOption('pageSize', $perPage);

        $query = $this->pagerQuery($page);
        $results = $query->results;

        $models = array_map(function ($result) {
            $model = clone $this->model;
            $model->attachDocument($result);

            return $model;
        }, $results);

        return Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items' => $this->model->newCollection($models),
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
     * Return the first item of the query.
     *
     * @return Model
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * Apply a where clause by anthing on the current query.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return QueryBuilder
     */
    public function where($field, $value)
    {
        if (! $this->isDocumentBaseField($field)) {
            $field = "my.{$this->model->getTypeName()}.{$field}";
        }

        $this->addPredicate(Predicates::at($field, $value));
        return $this;
    }

    /**
     * Apply a whereIn field clause on the current query.
     *
     * @param string $field
     * @param array  $values
     *
     * @return QueryBuilder
     */
    public function whereIn($field, array $values)
    {
        if (! $this->isDocumentBaseField($field)) {
            $field = "my.{$this->model->getTypeName()}.{$field}";
        }

        $this->addPredicate(Predicates::any($field, $values));
        return $this;
    }

    /**
     * Apply a tag filter on the query.
     *
     * @param array $tags
     *
     * @return QueryBuilder
     */
    public function whereTags(...$tags)
    {
        $this->where('document.tags', $tags);
        return $this;
    }

    /**
     * Apply a publication-date filter on the query. Either specify a date as a full
     * date e.g 2018-07-03 or use it in comibination with the method parameter e.g
     * $date = 'May' $method = 'month'.
     *
     * @param string $date
     *
     * @return QueryBuilder
     * @throws \InvalidArgumentException
     */
    public function wherePublicationDate($date, $method)
    {
        if (! empty($method)) {
            if (! in_array($method, $this->dateOptions)) {
                throw new \InvalidArgumentException("The method {$method} is not a valid date option.");
            }

            $this->addPredicate(Predicates::{$method}('document.first_publication_date', $date));
        }

        return $this;
    }

    /**
     * Fetch linked items with the response.
     *
     * @see https://prismic.io/docs/php/query-the-api/fetch-linked-document-fields
     *
     * @param array $fields
     *  e.g: article.title, article.body
     *
     * @return QueryBuilder
     */
    public function with(...$fields)
    {
        $this->options['fetchLinks'] = $fields;
        return $this;
    }

    /**
     * Order the query by one of your fields.
     *
     * @param string $field
     *  e.g: 'publication_date'
     *      or 'publication_date asc'
     *      or 'publication_date desc'
     *
     * @return QueryBuilder
     */
    public function orderBy($field)
    {
        $this->addOption('orderings', "[my.{$this->model->getTypeName()}.{$field}]");
        return $this;
    }

    /**
     * Order the results by the first publication date.
     *
     * @return $this
     */
    public function orderByFirstPublicationDate()
    {
        $this->addOption('orderings', '[document.first_publication_date]');
        return $this;
    }

    /**
     * Order the results by the last publication date.
     *
     * @return $this
     */
    public function orderByLastPublicationDate()
    {
        $this->addOption('orderings', '[document.last_publication_date]');
        return $this;
    }

    /**
     * Make the query for specific language.
     *
     * @param string $language
     */
    public function language($language)
    {
        $this->addOption('lang', $language);
    }

    /**
     * Add a new predicates to the predicates property.
     *
     * @param Predicate $predicate
     */
    public function addPredicate(Predicate $predicate)
    {
        $this->predicates[] = $predicate;
    }

    /**
     * Add a new Prismic query option.
     *
     * @param string $name
     * @param string $value
     */
    public function addOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Run a pager query.
     *
     * @param int $page
     * @return \stdClass
     */
    protected function pagerQuery($page = 1)
    {
        return $this->api()->query(
            $this->predicates,
            array_merge($this->options, ['page' => $page])
        );
    }

    /**
     * Only query specific fields.
     *
     * @param array $fields
     */
    protected function specifyFields(array $fields)
    {
        if (! empty($fields)) {
            $fields = array_map(function ($field) {
                return "my.{$this->model->getTypeName()}.{$field}";
            }, $fields);

            $this->addOption('fetch', implode(',', $fields));
        }
    }

    /**
     * Check if the given field name is one of the documents
     * or a custom field.
     *
     * @param string $fieldName
     * @return bool
     */
    protected function isDocumentBaseField($fieldName)
    {
        return explode('.', $fieldName)[0] == 'document';
    }

    /**
     * @return Api
     */
    public function api()
    {
        return $this->api;
    }
}
