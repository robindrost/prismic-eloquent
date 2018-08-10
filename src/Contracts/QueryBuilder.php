<?php

namespace RobinDrost\PrismicEloquent\Contracts;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface QueryBuilder
{

    /**
     * Return a single content type page based on the name of the content type.
     *
     * @return Model
     */
    public function single() : Model;

    /**
     * Find a content item based on its UID field.
     *
     * @param string $uid
     * @return Model
     */
    public function find(string $uid) : Model;

    /**
     * Find a content item based on the given ID.
     *
     * @param string $id
     * @return Model
     */
    public function findById(string $id) : Model;

    /**
     * Find multiple content items based on an array of ID's.
     *
     * @param array $ids
     * @return Collection
     */
    public function findByIds(array $ids) : Collection;

    /**
     * Find the first occurrence.
     *
     * @return Model
     */
    public function first() : Model;

    /**
     * Find all documents based on the current query scope.
     *
     * @return Collection
     */
    public function all() : Collection;

    /**
     * Get a paginated collection of the current query scope.
     *
     * @param int $perPage
     * @param array $fields
     * @param string $pageName
     * @param null $page
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 10,
        array $fields = [],
        string $pageName = 'page',
        $page = null
    ) : LengthAwarePaginator;

    /**
     * Set the content type name to match the given type.
     *
     * @param String $type
     * @return QueryBuilder
     */
    public function whereType(String $type) : QueryBuilder;

    /**
     * Apply a where tag clause on the query.
     *
     * @param String $tag
     * @return QueryBuilder
     */
    public function whereTag(String $tag) : QueryBuilder;

    /**
     * Apply a where clause on multiple document tags.
     *
     * @param array $tags
     * @return QueryBuilder
     */
    public function whereTags(array $tags) : QueryBuilder;

    /**
     * Apply a where clause on the query.
     *
     * @param string $field
     * @param mixed $value
     * @return QueryBuilder
     */
    public function where(string $field, $value) : QueryBuilder;

    /**
     * Apply a whereIn clause, e.g multiple field options.
     *
     * @param string $field
     * @param array $values
     * @return QueryBuilder
     */
    public function whereIn(string $field, array $values) : QueryBuilder;

    /**
     * Apply a whereNot clause on the query.
     *
     * @param string $field
     * @param mixed $value
     * @return QueryBuilder
     */
    public function whereNot(string $field, $value) : QueryBuilder;

    /**
     * Apply a where language clause on the query.
     *
     * @param $language
     * @return QueryBuilder
     */
    public function whereLanguage($language) : QueryBuilder;

    /**
     * Apply a full text where clause.
     *
     * @param $text
     * @return QueryBuilder
     */
    public function search($text) : QueryBuilder;

    /**
     * Use the Prismic fetch option to eager load fields. The fields are
     * later mapped to the given model.
     *
     * @param mixed $fields
     * @return QueryBuilder
     */
    public function fetch(...$fields) : QueryBuilder;

    /**
     * Only select specific fields on the current query.
     *
     * @param string ...$fields
     * @return QueryBuilder
     */
    public function select(...$fields) : QueryBuilder;

    /**
     * Order the query on a field and specify the sort option.
     *
     * @param string $field
     * @param string $sort
     * @return QueryBuilder
     */
    public function orderBy(string $field, $sort = 'desc') : QueryBuilder;
}
