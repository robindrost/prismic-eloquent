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
     * $this->hasOne(Article::class, 'my_relational_field_name')
     *
     * or multiple model options
     *
     * $this->hasOne(['article' => Article::class, 'person' => Person::class], 'my_relational_field_name')
     *
     * @param string|array $modelName
     * @param string $fieldName
     *
     * @return Model
     */
    protected function hasOne($modelName, $fieldName)
    {
        if (! $this->document->data->{$fieldName} instanceof Model
            && $this->relationHasDocument($this->document->data->{$fieldName})) {
            if (is_array($modelName)) {
                $modelName = $modelName[$this->document->data->{$fieldName}->type];
            }

            $this->document->data->{$fieldName} = $modelName::newInstance($this->document->data->{$fieldName});
        }

        return $this->document->data->{$fieldName};
    }

    /**
     * Define a relation on your related fields. Note that this field must
     * hold data that can be used by the given model.
     *
     * $this->hasMany(Article::class, 'my_relational_field_name')
     *
     * You can define fields like this in Prismic with the array operator:
     * my_relational_field_name[0]
     * my_relational_field_name[1]
     * my_relational_field_name[2]
     *
     * @param string $modelName
     * @param string $fieldName
     *
     * @return Collection
     */
    protected function hasMany($modelName, $fieldName)
    {
        return collect($this->document->data->{$fieldName})->map(function ($relation) use ($modelName) {
            if (! $relation instanceof Model && $this->relationHasDocument($relation)) {
                if (is_array($modelName)) {
                    $modelName = $modelName[$relation->type];
                }

                $relation = $modelName::newInstance($relation);
            }

            return $relation;
        })->filter(function ($model) {
            return $model instanceof Model;
        });
    }

    /**
     * Define relation for relational fields that live inside a group field.
     *
     * $this->hasOneInGroup(Article::class, 'group_field_name', 'relation_field_name')
     *
     * @param string $modelName
     * @param string $groupFieldName
     * @param string $fieldName
     *
     * @return Collection
     */
    protected function hasOneInGroup($modelName, $groupFieldName, $fieldName)
    {
        if ((! empty($group = $this->field($groupFieldName))) && is_array($group)) {
            return collect($group)->map(function ($field) use ($modelName, $fieldName) {
                if (! $field->{$fieldName} instanceof Model && $this->relationHasDocument($field->{$fieldName})) {
                    if (is_array($modelName)) {
                        $modelName = $modelName[$field->{$fieldName}->type];
                    }

                    $field->{$fieldName} = $modelName::newInstance($field->{$fieldName});
                }

                return $field;
            })->filter(function ($group) use ($fieldName) {
                return $group->{$fieldName} instanceof Model;
            });
        }

        return collect([]);
    }

    /**
     * Check if a relation has a document.
     *
     * @return bool
     */
    protected function relationHasDocument($relation)
    {
        return ! empty($relation)
            && property_exists($relation, 'isBroken')
            && ! $relation->isBroken
            && property_exists($relation, 'id');
    }

    /**
     * Return the Prismic type content type name.
     *
     * @return string
     */
    abstract public function getTypeName();
}
