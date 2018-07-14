<?php

namespace RobinDrost\PrismicEloquent;

use Illuminate\Support\Collection;

abstract class Model
{

    /**
     * @var \stdClass
     */
    public $document;

    /**
     * @var int
     */
    protected $perPage = 10;

    /**
     * Model constructor.
     *
     * @param \stdClass $document|null
     */
    public function __construct($document = null)
    {
        if (! empty($document)) {
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
        if (! empty($this->document)) {
            $method = 'get' . ucfirst($key);

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }

            if (! empty($fieldValue = $this->field($key))) {
                return $fieldValue;
            }

            if (! empty($attributeValue = $this->attribute($key))) {
                return $attributeValue;
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
     * Return a value from the Prismic data object.
     *
     * @param string $key
     * @return mixed|null
     */
    protected function field($key)
    {
        if (! empty($data = $this->attribute('data'))) {
            if (property_exists($data, $key)) {
                return $data->{$key};
            }
        }
    }

    /**
     * Returns a document attribute e.g id, uid, publication_date.
     *
     * @param string $key
     * @return mixed|null
     */
    protected function attribute($key)
    {
        if (property_exists($this->document, $key)) {
            return $this->document->{$key};
        }
    }

    /**
     * Return a new query builder instance.
     *
     * @return QueryBuilder
     */
    protected function newQuery()
    {
        return new QueryBuilder($this);
    }

    /**
     * @return integer
     */
    protected function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Define a relation on your related fields. Note that this field must
     * hold data that can be used by the given model.
     *
     * $this->relation(Article::class, 'my_relational_field_name')
     *
     * @param string $modelName
     * @param string $fieldName
     *
     * @return Model
     */
    protected function relation($modelName, $fieldName)
    {
        if (! $this->document->data->{$fieldName} instanceof Model) {
            $this->document->data->{$fieldName} = $modelName::newInstance($this->document->data->{$fieldName});
        }

        return $this->document->data->{$fieldName};
    }

    /**
     * Define relation for relational fields that live inside a group field.
     *
     * $this->relation(Article::class, 'group_field_name', 'relation_field_name')
     *
     * @param string $modelName
     * @param string $groupFieldName
     * @param string $fieldName
     *
     * @return Collection
     */
    protected function relations($modelName, $groupFieldName, $fieldName)
    {
        if ((! empty($group = $this->field($groupFieldName))) && is_array($group)) {
            return collect($group)->map(function ($field) use ($modelName, $fieldName) {
                if (! $field->{$fieldName} instanceof Model) {
                    $field->{$fieldName} = $modelName::newInstance($field->{$fieldName});
                }

                return $field;
            });
        }

        return collect([]);
    }

    /**
     * Return the Prismic type content type name.
     *
     * @return string
     */
    abstract public function getTypeName();
}
