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

            if ($this->hasField($key)) {
                return $this->field($key);
            }

            if ($this->hasAttribute($key)) {
                return $this->attribute($key);
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
    public function field($key)
    {
        if ($this->fieldsToSnakeCase) {
            $key = snake_case($key);
        }

        if (! empty($data = $this->attribute('data'))) {
            if (property_exists($data, $key)) {
                return $data->{$key};
            }
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

        return ! empty($this->field($fieldName));
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
        return ! empty($this->attribute($attributeName));
    }

    /**
     * Return the current active slug.
     *
     * @return string|null
     */
    public function slug()
    {
        if ($this->hasAttribute('slugs')) {
            return $this->attribute('slugs')[0];
        }
    }

    /**
     * Return the history of slugs.
     *
     * @return array|null
     */
    public function slugs()
    {
        if ($this->hasAttribute('slugs')) {
            return $this->attribute('slugs');
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
     * $this->hasOne(Article::class, 'related_article', ['title', 'body'])
     *
     * or multiple model options
     *
     * $this->hasOne(['article' => Article::class, 'person' => Person::class], 'related_article', ['title'])
     *
     * @param string|array $modelName
     * @param string $fieldName
     * @param array $fieldsToFetch
     *
     * @return Relationship
     */
    protected function hasOne($modelName, $fieldName, array $fieldsToFetch)
    {
        return new Relationship($modelName, $fieldName, $fieldsToFetch);
    }

    /**
     * The has many method is ment to be used on a group that holds a single
     * field to a relation. Prismic describes this way to create a repeatable
     * relation.
     *
     * This method will overwrite the array of the group with a collection
     * that holds all the relational data.
     *
     * $this->hasMany(Article::class, 'group_field', 'field', ['title', 'body'])
     *
     * @param string $modelName
     * @param string $groupField
     * @param string $fieldName
     * @param array $fieldsToFetch
     *
     * @return Collection
     * @throws \InvalidArguementException
     */
    protected function hasMany($modelName, $groupField, $fieldName, array $fieldsToFetch)
    {
        return new Relationship($modelName, $fieldName, $fieldsToFetch, $groupField);
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
