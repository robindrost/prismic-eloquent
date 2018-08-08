<?php

namespace RobinDrost\PrismicEloquent;

use Illuminate\Support\Collection;

abstract class Model
{

    use HasRelationships;

    /**
     * @var \stdClass
     */
    public $document;

    /**
     * @var int
     */
    protected $perPage = 10;

    /**
     * @var bool
     */
    protected $fieldsToSnakeCase = true;

    /**
     * Model constructor.
     *
     * @param \stdClass $document|null
     */
    public function __construct($document = null)
    {
        if (!empty($document)) {
            $this->attachDocument($document);
        }
    }

    /**
     * Helper that will check if the given property exstis inside
     * the document data object.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (!empty($this->document)) {
            $method = 'get' . ucfirst(camel_case($key)) . 'Attribute';

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }

            if ($this->hasAttribute($key)) {
                return $this->attribute($key);
            }

            if ($this->hasField($key)) {
                return $this->field($key);
            }
        }

        return $this->{$key};
    }

    /**
     * Call a method on the query builder.
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->newQuery()->{$method}(...$arguments);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return (new static)->$method(...$arguments);
    }

    /**
     * Create a new instance of the model.
     *
     * @param  \stdClass $data
     * @return static
     */
    public static function newInstance($data)
    {
        return new static($data);
    }

    /**
     * Return a new collection of models.
     *
     * @param  array $items
     * @return Collection
     */
    public function newCollection(array $items)
    {
        return collect($items);
    }

    /**
     * Attach a document object.
     *
     * @param \stdClass $data
     */
    public function attachDocument($data)
    {
        $this->document = $data;
    }

    /**
     * Short hand method for getField.
     *
     * @param string $key
     * @return mixed|null
     */
    public function field($key)
    {
        if ($this->fieldsToSnakeCase) {
            $key = snake_case($key);
        }

        if (property_exists($this->document->data, $key)) {
            return $this->document->data->{$key};
        }
    }

    /**
     * Check if the given field name is available.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        if ($this->fieldsToSnakeCase) {
            $fieldName = snake_case($fieldName);
        }

        return !empty($this->field($fieldName));
    }

    /**
     * Returns a document attribute e.g id, uid, publication_date.
     *
     * @param string $key
     * @return mixed|null
     */
    public function attribute($key)
    {
        if (property_exists($this->document, $key)) {
            return $this->document->{$key};
        }
    }

    /**
     * Check if the given attribute name is available.
     *
     * @param string $attributeName
     *
     * @return bool
     */
    public function hasAttribute($attributeName)
    {
        return !empty($this->attribute($attributeName));
    }

    /**
     * Return a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return new QueryBuilder($this);
    }

    /**
     * Return a new query builder instance without a type specified.
     *
     * @return QueryBuilder
     */
    public function newEmptyQuery()
    {
        return new QueryBuilder($this, false);
    }

    /**
     * @return integer
     */
    protected function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Return the Prismic type content type name. By default it will
     * use the current class name as the content type name.
     *
     * It will transform the current class to lower / snake case.
     * You can always override this method when it fails.
     *
     * @return string
     */
    public static function getTypeName()
    {
        $fullPath = explode('\\', get_called_class());
        return snake_case(array_pop($fullPath));
    }
}
