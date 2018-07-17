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
     * @var array
     */
    protected $relations = [];

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

            if ($this->hasRelation($key)) {
                return $this->relation($key);
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
    protected function field($key)
    {
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
        return ! empty($this->field($fieldName));
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
     * Returns a relation.
     *
     * @param string $key
     * @return mixed|null
     */
    public function relation($key)
    {
        return $this->relations[$key];
    }

    /**
     * Check if the given attribute name is available.
     *
     * @param string $relationName
     *
     * @return bool
     */
    public function hasRelation($relationName)
    {
        return array_key_exists($relationName, $this->relations);
    }

    /**
     * Return a new query builder instance.
     *
     * @param array $possibleTypes
     *  ['article' => Article::class, 'blog_post' => BlogPost::class]
     *
     * @return QueryBuilder
     */
    protected function newQuery(array $possibleTypes = [])
    {
        return new QueryBuilder($this, $possibleTypes);
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
     * $this->hasOne('my_relational_field_name', ['article' => Article::class])
     *
     * or multiple content type options
     *
     * $this->hasOne('my_relational_field_name', ['article' => Article::class, 'person' => Person::class])
     *
     * @param string $fieldName
     * @param array  $typeModels
     *
     * @return Model
     */
    protected function hasOne($fieldName, array $typeModels)
    {
        if ($this->hasRelation($fieldName)) {
            return $this->relation($fieldName);
        }

        $this->relations[$fieldName] = $this->newQuery($typeModels)->findById($this->field($fieldName)->id);

        return $this->relation($fieldName);
    }

    /**
     * Define a relation on your related fields. Note that this field must
     * hold data that can be used by the given model.
     *
     * $this->hasMany('my_relational_field_name', ['article' => Article::class])
     *
     * or multiple content type options
     *
     * $this->hasMany('my_relational_field_name', ['article' => Article::class, 'person' => Person::class])
     *
     * You can define fields like this in Prismic with the array operator:
     * my_relational_field_name[0]
     * my_relational_field_name[1]
     * my_relational_field_name[2]
     *
     * @param string $fieldName
     *
     * @return Collection
     * @throws \InvalidArguementException
     */
    protected function hasMany($fieldName, array $typeModels)
    {
        if ($this->hasRelation($fieldName)) {
            return $this->relation($fieldName);
        }

        $ids = [];

        foreach ($this->field($fieldName) as $document) {
            $ids[] = $document->id;
        }

        $this->relations[$fieldName] = $this->newQuery($typeModels)->whereIn('document.id', $ids)->get();

        return $this->relation($fieldName);
    }

    /**
     * Define relation for relational fields that live inside a group field.
     *
     * $this->hasManyThroughGroup('group_field_name', 'relation_field_name', Article::class)
     *
     * @param string $groupName
     * @param string $fieldName
     * @param array  $typeModels
     *
     * @return Collection
     * @throws \InvalidArguementException
     */
    protected function hasManyThroughGroup($groupName, $fieldName, array $typeModels)
    {
        if ($this->hasRelation($groupName)) {
            return $this->relation($groupName);
        }

        $ids = [];

        foreach ($this->field($groupName) as $group) {
            $ids[] = $group->{$fieldName}->id;
        }

        $this->relations[$groupName] = $this->newQuery($typeModels)->whereIn('document.id', $ids)->get();

        return $this->relation($groupName);
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
    public function getTypeName()
    {
        $fullPath = explode('\\', get_class($this));
        return snake_case(array_pop($fullPath));
    }
}
