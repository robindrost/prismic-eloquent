<?php

namespace RobinDrost\PrismicEloquent\Contracts;

use Illuminate\Support\Collection;

interface Model
{

    /**
     * Return a document attribute e.g ID, UID, language etc.
     *
     * @param string $name
     * @return mixed
     */
    public function attribute(string $name);

    /**
     * Check if the given attribute exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name) : bool;

    /**
     * Return a field from the current data object.
     *
     * @param string $name
     * @return mixed
     */
    public function field(string $name);

    /**
     * Check if a field exists inside the data object.
     *
     * @param string $name
     * @return bool
     */
    public function hasField(string $name) : bool;

    /**
     * Create a new query builder.
     *
     * @return QueryBuilder
     */
    public function newQuery() : QueryBuilder;

    /**
     * Attach a document object to the model.
     *
     * @param mixed $document
     * @return Model
     */
    public function attachDocument($document) : Model;

    /**
     * Return the content type name of the model.
     *
     * @return string
     */
    public static function getTypeName() : string;

    /**
     * Return a new instance of the model.
     *
     * @param mixed $document
     * @return Model
     */
    public static function newInstance($document) : Model;

    /**
     * Specify the collection that should get returned for this model.
     *
     * @param array $models
     * @return Collection
     */
    public static function newCollection(array $models) : Collection;
}
